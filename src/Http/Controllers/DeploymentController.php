<?php

declare(strict_types=1);

namespace Omaralalwi\LaravelForgeDeployments\Http\Controllers;

use DateTimeInterface;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Omaralalwi\LaravelForgeDeployments\Data\DeploymentSiteStatus;
use Omaralalwi\LaravelForgeDeployments\Data\RemoteDeployment;
use Omaralalwi\LaravelForgeDeployments\Enums\DeploymentFailureReason;
use Omaralalwi\LaravelForgeDeployments\Enums\DeploymentStatus;
use Omaralalwi\LaravelForgeDeployments\Exceptions\DeploymentAlreadyActiveException;
use Omaralalwi\LaravelForgeDeployments\Exceptions\DeploymentGatewayException;
use Omaralalwi\LaravelForgeDeployments\Exceptions\DeploymentNotConfiguredException;
use Omaralalwi\LaravelForgeDeployments\Http\Requests\ListDeploymentHistoryRequest;
use Omaralalwi\LaravelForgeDeployments\Models\DeploymentRun;
use Omaralalwi\LaravelForgeDeployments\Services\DeploymentManager;
use Omaralalwi\LaravelForgeDeployments\Support\DeploymentConfiguration;

final class DeploymentController extends Controller
{
    public function __construct(
        private readonly DeploymentManager $manager,
        private readonly DeploymentConfiguration $configuration,
    ) {}

    public function index(): View
    {
        $state = [
            'configured' => $this->configuration->isReady(),
            'available' => false,
            'configuration_message' => $this->configurationMessage(),
            'target' => [
                'label' => (string) config('forge-deployments.target.label', ''),
                'branch' => (string) config('forge-deployments.target.branch', ''),
            ],
            'current' => $this->statusPayload(new DeploymentSiteStatus(DeploymentStatus::Unknown)),
            'history' => ['items' => [], 'next_cursor' => null],
            'locale' => app()->getLocale(),
            'poll_interval_ms' => $this->configuration->pollIntervalMilliseconds(),
            'endpoints' => [
                'trigger' => route($this->routeName('store')),
                'current' => route($this->routeName('current')),
                'history' => route($this->routeName('history')),
            ],
            'messages' => [
                'triggered' => __('laravel-forge-deployments::deployments.triggered'),
                'unexpected_error' => __('laravel-forge-deployments::deployments.errors.unavailable'),
                'empty_output' => __('laravel-forge-deployments::deployments.empty_output'),
                'pending_id' => __('laravel-forge-deployments::deployments.pending_id'),
                'unknown_commit' => __('laravel-forge-deployments::deployments.unknown_commit'),
                'external_actor' => __('laravel-forge-deployments::deployments.external_actor'),
                'load_more' => __('laravel-forge-deployments::deployments.load_more'),
                'loading_more' => __('laravel-forge-deployments::deployments.loading_more'),
                'duration_seconds' => __('laravel-forge-deployments::deployments.duration_seconds'),
                'duration_minutes' => __('laravel-forge-deployments::deployments.duration_minutes'),
            ],
        ];

        if ($state['configured']) {
            try {
                $state['current'] = $this->statusPayload($this->manager->currentStatus());
                $state['history'] = $this->historyPayload();
                $state['available'] = true;
            } catch (DeploymentGatewayException $exception) {
                $state['configuration_message'] = $this->gatewayMessage($exception);
            }
        }

        return view((string) config(
            'forge-deployments.view',
            'laravel-forge-deployments::dashboard',
        ), ['deploymentState' => $state]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user instanceof Authenticatable) {
            abort(403);
        }

        try {
            $run = $this->manager->trigger($user);

            return response()->json([
                'message' => __('laravel-forge-deployments::deployments.triggered'),
                'deployment' => $this->runPayload($run),
            ], 202);
        } catch (DeploymentAlreadyActiveException) {
            return response()->json([
                'message' => __('laravel-forge-deployments::deployments.errors.already_active'),
            ], 409);
        } catch (DeploymentNotConfiguredException) {
            return response()->json([
                'message' => __('laravel-forge-deployments::deployments.errors.not_configured'),
            ], 503);
        } catch (DeploymentGatewayException $exception) {
            return $this->gatewayErrorResponse($exception);
        }
    }

    public function current(): JsonResponse
    {
        try {
            return response()->json(['current' => $this->statusPayload($this->manager->currentStatus())]);
        } catch (DeploymentNotConfiguredException) {
            return response()->json([
                'message' => __('laravel-forge-deployments::deployments.errors.not_configured'),
            ], 503);
        } catch (DeploymentGatewayException $exception) {
            return $this->gatewayErrorResponse($exception);
        }
    }

    public function status(DeploymentRun $deploymentRun): JsonResponse
    {
        try {
            return response()->json([
                'deployment' => $this->remotePayload(
                    $this->manager->refresh($deploymentRun),
                    $deploymentRun->uuid,
                    $deploymentRun->actor_name,
                    $deploymentRun->actor_email,
                    $deploymentRun->source,
                ),
            ]);
        } catch (DeploymentNotConfiguredException) {
            return response()->json([
                'message' => __('laravel-forge-deployments::deployments.errors.not_configured'),
            ], 503);
        } catch (DeploymentGatewayException $exception) {
            return $this->gatewayErrorResponse($exception);
        }
    }

    public function history(ListDeploymentHistoryRequest $request): JsonResponse
    {
        try {
            return response()->json(['history' => $this->historyPayload($request->cursor())]);
        } catch (DeploymentNotConfiguredException) {
            return response()->json([
                'message' => __('laravel-forge-deployments::deployments.errors.not_configured'),
            ], 503);
        } catch (DeploymentGatewayException $exception) {
            return $this->gatewayErrorResponse($exception);
        }
    }

    public function output(int $deploymentId): JsonResponse
    {
        try {
            return response()->json([
                'deployment_id' => $deploymentId,
                'output' => $this->manager->output($deploymentId),
            ]);
        } catch (DeploymentNotConfiguredException) {
            return response()->json([
                'message' => __('laravel-forge-deployments::deployments.errors.not_configured'),
            ], 503);
        } catch (DeploymentGatewayException $exception) {
            return $this->gatewayErrorResponse($exception);
        }
    }

    /** @return array{value: string, label: string, active: bool, terminal: bool, started_at: ?string} */
    private function statusPayload(DeploymentSiteStatus $status): array
    {
        return [
            'value' => $status->status->value,
            'label' => __("laravel-forge-deployments::deployments.status.{$status->status->value}"),
            'active' => $status->status->isActive(),
            'terminal' => $status->status->isTerminal(),
            'started_at' => $status->startedAt?->format(DateTimeInterface::ATOM),
        ];
    }

    /** @return array<string, mixed> */
    private function runPayload(DeploymentRun $run): array
    {
        $status = $run->status instanceof DeploymentStatus ? $run->status : DeploymentStatus::Unknown;
        $deploymentId = $run->forge_deployment_id !== null ? (int) $run->forge_deployment_id : null;

        return [
            'id' => $deploymentId,
            'local_run_uuid' => $run->uuid,
            'status' => $this->statusPayload(new DeploymentSiteStatus(
                $status,
                $run->started_at?->toDateTimeImmutable(),
            )),
            'actor_name' => $run->actor_name,
            'actor_email' => $run->actor_email,
            'source' => $run->source,
            'source_label' => __("laravel-forge-deployments::deployments.sources.{$run->source}"),
            'branch' => $run->branch,
            'requested_at' => $run->requested_at?->toAtomString(),
            'started_at' => $run->started_at?->toAtomString(),
            'ended_at' => $run->ended_at?->toAtomString(),
            'duration_seconds' => null,
            'status_url' => route($this->routeName('status'), $run),
            'output_url' => $deploymentId !== null
                ? route($this->routeName('output'), ['deploymentId' => $deploymentId])
                : null,
        ];
    }

    /** @return array<string, mixed> */
    private function remotePayload(
        RemoteDeployment $deployment,
        ?string $localRunUuid = null,
        ?string $actorName = null,
        ?string $actorEmail = null,
        string $source = 'forge_external',
    ): array {
        $startedTimestamp = $deployment->startedAt?->getTimestamp();
        $endedTimestamp = $deployment->endedAt?->getTimestamp();

        return [
            'id' => $deployment->id,
            'local_run_uuid' => $localRunUuid,
            'status' => $this->statusPayload(new DeploymentSiteStatus(
                $deployment->status,
                $deployment->startedAt,
            )),
            'actor_name' => $actorName,
            'actor_email' => $actorEmail,
            'source' => $source,
            'source_label' => __("laravel-forge-deployments::deployments.sources.{$source}"),
            'type' => $deployment->type,
            'commit_hash' => $deployment->commitHash,
            'commit_author' => $deployment->commitAuthor,
            'commit_message' => $deployment->commitMessage,
            'branch' => $deployment->branch,
            'started_at' => $deployment->startedAt?->format(DateTimeInterface::ATOM),
            'ended_at' => $deployment->endedAt?->format(DateTimeInterface::ATOM),
            'created_at' => $deployment->createdAt?->format(DateTimeInterface::ATOM),
            'duration_seconds' => $startedTimestamp !== null && $endedTimestamp !== null
                ? max(0, $endedTimestamp - $startedTimestamp)
                : null,
            'status_url' => $localRunUuid !== null
                ? route($this->routeName('status'), ['deploymentRun' => $localRunUuid])
                : null,
            'output_url' => route($this->routeName('output'), ['deploymentId' => $deployment->id]),
        ];
    }

    /** @return array{items: list<array<string, mixed>>, next_cursor: ?string} */
    private function historyPayload(?string $cursor = null): array
    {
        $result = $this->manager->history($cursor);
        $page = $result['page'];
        $runs = $result['runs'];
        $items = [];

        if ($cursor === null) {
            foreach ($result['unresolved'] as $run) {
                $items[] = $this->runPayload($run);
            }
        }

        foreach ($page->deployments as $deployment) {
            $run = $runs->get($deployment->id);

            if ($run instanceof DeploymentRun) {
                $items[] = $this->remotePayload(
                    $deployment,
                    $run->uuid,
                    $run->actor_name,
                    $run->actor_email,
                    $run->source,
                );

                continue;
            }

            $items[] = $this->remotePayload($deployment);
        }

        return ['items' => $items, 'next_cursor' => $page->nextCursor];
    }

    private function configurationMessage(): ?string
    {
        return match ($this->configuration->problem()) {
            'disabled' => __('laravel-forge-deployments::deployments.errors.disabled'),
            'incomplete' => __('laravel-forge-deployments::deployments.errors.not_configured'),
            default => null,
        };
    }

    private function gatewayMessage(DeploymentGatewayException $exception): string
    {
        return __("laravel-forge-deployments::deployments.errors.{$exception->reason->value}");
    }

    private function gatewayErrorResponse(DeploymentGatewayException $exception): JsonResponse
    {
        $status = $exception->reason === DeploymentFailureReason::RateLimited ? 429 : 503;
        $response = response()->json([
            'message' => $this->gatewayMessage($exception),
            'reason' => $exception->reason->value,
            'retry_at' => $exception->retryAt,
        ], $status);

        if ($exception->retryAt !== null) {
            $response->headers->set('Retry-After', (string) max(1, $exception->retryAt - time()));
        }

        return $response;
    }

    private function routeName(string $name): string
    {
        return (string) config('forge-deployments.route.name_prefix', 'forge-deployments.').$name;
    }
}
