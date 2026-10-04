<?php

declare(strict_types=1);

namespace Omaralalwi\LaravelForgeDeployments\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Omaralalwi\LaravelForgeDeployments\Contracts\DeploymentGateway;
use Omaralalwi\LaravelForgeDeployments\Contracts\ResolvesDeploymentActor;
use Omaralalwi\LaravelForgeDeployments\Data\DeploymentHistoryPage;
use Omaralalwi\LaravelForgeDeployments\Data\DeploymentSiteStatus;
use Omaralalwi\LaravelForgeDeployments\Data\RemoteDeployment;
use Omaralalwi\LaravelForgeDeployments\Enums\DeploymentFailureReason;
use Omaralalwi\LaravelForgeDeployments\Enums\DeploymentStatus;
use Omaralalwi\LaravelForgeDeployments\Exceptions\DeploymentAlreadyActiveException;
use Omaralalwi\LaravelForgeDeployments\Exceptions\DeploymentGatewayException;
use Omaralalwi\LaravelForgeDeployments\Models\DeploymentRun;
use Omaralalwi\LaravelForgeDeployments\Support\DeploymentConfiguration;

final readonly class DeploymentManager
{
    public function __construct(
        private DeploymentGateway $gateway,
        private DeploymentConfiguration $configuration,
        private ResolvesDeploymentActor $actorResolver,
    ) {}

    public function currentStatus(): DeploymentSiteStatus
    {
        $target = $this->configuration->target();
        $localRun = DeploymentRun::query()
            ->where('forge_server_id', $target->serverId)
            ->where('forge_site_id', $target->siteId)
            ->whereIn('status', $this->activeStatusValues())
            ->latest('requested_at')
            ->first();

        if ($localRun?->forge_deployment_id !== null) {
            $deployment = $this->gateway->find($target, (int) $localRun->forge_deployment_id);
            $localRun->applyRemoteDeployment($deployment);

            return new DeploymentSiteStatus($deployment->status, $deployment->startedAt);
        }

        if ($localRun !== null && $localRun->requested_at->isAfter(
            now()->subSeconds($this->configuration->ambiguousRequestTtlSeconds()),
        )) {
            return new DeploymentSiteStatus(DeploymentStatus::Unknown, $localRun->requested_at->toDateTimeImmutable());
        }

        return $this->gateway->currentStatus($target);
    }

    public function trigger(Authenticatable $user): DeploymentRun
    {
        $target = $this->configuration->target();
        $lock = Cache::lock("laravel-forge-deployments:site:{$target->siteId}:trigger", 20);

        if (! $lock->get()) {
            throw new DeploymentAlreadyActiveException;
        }

        try {
            if ($this->currentStatus()->status->isActive()) {
                throw new DeploymentAlreadyActiveException;
            }

            $actor = $this->actorResolver->resolve($user);
            $run = DeploymentRun::query()->create([
                'uuid' => (string) Str::uuid(),
                'actor_type' => $actor->type,
                'actor_id' => $actor->id,
                'actor_name' => $actor->name,
                'actor_email' => $actor->email,
                'source' => 'dashboard',
                'forge_server_id' => $target->serverId,
                'forge_site_id' => $target->siteId,
                'status' => DeploymentStatus::Requesting,
                'branch' => $target->branch,
                'requested_at' => now(),
            ]);

            try {
                $deployment = $this->gateway->trigger($target);
                $run->applyRemoteDeployment($deployment);
            } catch (DeploymentGatewayException $exception) {
                $run->forceFill([
                    'status' => DeploymentStatus::Unknown,
                    'failure_code' => $exception->reason->value,
                    'last_synced_at' => now(),
                ])->save();

                Log::warning('Forge deployment request requires reconciliation.', [
                    'deployment_run_id' => $run->getKey(),
                    'actor_id' => $actor->id,
                    'forge_site_id' => $target->siteId,
                    'failure_code' => $exception->reason->value,
                ]);

                throw $exception;
            }

            Log::notice('Forge deployment requested.', [
                'deployment_run_id' => $run->getKey(),
                'forge_deployment_id' => $run->forge_deployment_id,
                'actor_id' => $actor->id,
                'forge_site_id' => $target->siteId,
            ]);

            return $run->refresh();
        } finally {
            $lock->release();
        }
    }

    public function refresh(DeploymentRun $run): RemoteDeployment
    {
        $target = $this->configuration->target();

        if ((int) $run->forge_server_id !== $target->serverId
            || (int) $run->forge_site_id !== $target->siteId) {
            abort(404);
        }

        if ($run->forge_deployment_id === null) {
            throw new DeploymentGatewayException(DeploymentFailureReason::InvalidResponse);
        }

        $deployment = $this->gateway->find($target, (int) $run->forge_deployment_id);
        $run->applyRemoteDeployment($deployment);

        return $deployment;
    }

    /**
     * @return array{page: DeploymentHistoryPage, runs: Collection<int, DeploymentRun>, unresolved: Collection<int, DeploymentRun>}
     */
    public function history(?string $cursor = null): array
    {
        $target = $this->configuration->target();
        $page = $this->gateway->history($target, $cursor, $this->configuration->historyPerPage());
        $deploymentIds = array_map(
            static fn (RemoteDeployment $deployment): int => $deployment->id,
            $page->deployments,
        );
        $runs = DeploymentRun::query()
            ->where('forge_server_id', $target->serverId)
            ->where('forge_site_id', $target->siteId)
            ->whereIn('forge_deployment_id', $deploymentIds)
            ->get()
            ->keyBy('forge_deployment_id');

        foreach ($page->deployments as $deployment) {
            $run = $runs->get($deployment->id);

            if ($run instanceof DeploymentRun) {
                $run->applyRemoteDeployment($deployment);
            }
        }

        $unresolved = new Collection;

        if ($cursor === null || $cursor === '') {
            $unresolved = DeploymentRun::query()
                ->where('forge_server_id', $target->serverId)
                ->where('forge_site_id', $target->siteId)
                ->whereNull('forge_deployment_id')
                ->latest('requested_at')
                ->limit(10)
                ->get();
        }

        return compact('page', 'runs', 'unresolved');
    }

    public function output(int $deploymentId): string
    {
        return $this->gateway->output($this->configuration->target(), $deploymentId);
    }

    /** @return list<string> */
    private function activeStatusValues(): array
    {
        return array_map(
            static fn (DeploymentStatus $status): string => $status->value,
            array_filter(
                DeploymentStatus::cases(),
                static fn (DeploymentStatus $status): bool => $status->isActive(),
            ),
        );
    }
}
