<?php

declare(strict_types=1);

namespace Omaralalwi\LaravelForgeDeployments\Services;

use Carbon\CarbonImmutable;
use GuzzleHttp\Exception\GuzzleException;
use Laravel\Forge\Exceptions\FailedActionException;
use Laravel\Forge\Exceptions\ForbiddenException;
use Laravel\Forge\Exceptions\NotFoundException;
use Laravel\Forge\Exceptions\RateLimitExceededException;
use Laravel\Forge\Exceptions\TimeoutException;
use Laravel\Forge\Exceptions\ValidationException;
use Laravel\Forge\Forge;
use Laravel\Forge\Resources\Deployment;
use Omaralalwi\LaravelForgeDeployments\Contracts\DeploymentGateway;
use Omaralalwi\LaravelForgeDeployments\Data\DeploymentHistoryPage;
use Omaralalwi\LaravelForgeDeployments\Data\DeploymentSiteStatus;
use Omaralalwi\LaravelForgeDeployments\Data\DeploymentTarget;
use Omaralalwi\LaravelForgeDeployments\Data\RemoteDeployment;
use Omaralalwi\LaravelForgeDeployments\Enums\DeploymentFailureReason;
use Omaralalwi\LaravelForgeDeployments\Enums\DeploymentStatus;
use Omaralalwi\LaravelForgeDeployments\Exceptions\DeploymentGatewayException;
use Throwable;

final readonly class ForgeDeploymentGateway implements DeploymentGateway
{
    public function __construct(private Forge $forge) {}

    public function currentStatus(DeploymentTarget $target): DeploymentSiteStatus
    {
        $response = $this->request(fn (): array => $this->forge->deploymentStatus(
            $target->organizationSlug,
            $target->serverId,
            $target->siteId,
        ));
        $attributes = is_array($response['attributes'] ?? null) ? $response['attributes'] : $response;

        return new DeploymentSiteStatus(
            status: DeploymentStatus::fromProvider($attributes['status'] ?? null),
            startedAt: $this->date($attributes['started_at'] ?? null),
        );
    }

    public function trigger(DeploymentTarget $target): RemoteDeployment
    {
        $deployment = $this->request(fn (): Deployment => $this->forge->createDeployment(
            $target->organizationSlug,
            $target->serverId,
            $target->siteId,
        ));

        return $this->mapDeployment($deployment);
    }

    public function find(DeploymentTarget $target, int $deploymentId): RemoteDeployment
    {
        $deployment = $this->request(fn (): Deployment => $this->forge->deployment(
            $target->organizationSlug,
            $target->serverId,
            $target->siteId,
            $deploymentId,
        ));

        return $this->mapDeployment($deployment);
    }

    public function history(
        DeploymentTarget $target,
        ?string $cursor = null,
        int $perPage = 20,
    ): DeploymentHistoryPage {
        $page = ['size' => $perPage];

        if ($cursor !== null && $cursor !== '') {
            $page['cursor'] = $cursor;
        }

        $paginator = $this->request(fn () => $this->forge->deployments(
            $target->organizationSlug,
            $target->serverId,
            $target->siteId,
            ['sort' => '-created_at', 'page' => $page],
        ));

        return new DeploymentHistoryPage(
            deployments: array_map(
                fn (Deployment $deployment): RemoteDeployment => $this->mapDeployment($deployment),
                $paginator->items(),
            ),
            nextCursor: $paginator->nextCursor(),
        );
    }

    public function output(DeploymentTarget $target, int $deploymentId): string
    {
        return $this->request(fn (): string => $this->forge->deploymentLog(
            $target->organizationSlug,
            $target->serverId,
            $target->siteId,
            $deploymentId,
        ));
    }

    private function mapDeployment(Deployment $deployment): RemoteDeployment
    {
        if ($deployment->id === null) {
            throw new DeploymentGatewayException(DeploymentFailureReason::InvalidResponse);
        }

        $commit = is_array($deployment->commit) ? $deployment->commit : [];

        return new RemoteDeployment(
            id: $deployment->id,
            status: DeploymentStatus::fromProvider($deployment->status),
            type: $deployment->type,
            commitHash: $this->nullableString($commit['hash'] ?? null),
            commitAuthor: $this->nullableString($commit['author'] ?? null),
            commitMessage: $this->nullableString($commit['message'] ?? null),
            branch: $this->nullableString($commit['branch'] ?? null),
            startedAt: $this->date($deployment->startedAt),
            endedAt: $this->date($deployment->endedAt),
            createdAt: $this->date($deployment->createdAt),
            updatedAt: $this->date($deployment->updatedAt),
        );
    }

    private function date(mixed $value): ?CarbonImmutable
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return CarbonImmutable::parse($value);
        } catch (Throwable) {
            return null;
        }
    }

    private function nullableString(mixed $value): ?string
    {
        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }

    private function request(callable $request): mixed
    {
        try {
            return $request();
        } catch (RateLimitExceededException $exception) {
            throw new DeploymentGatewayException(
                DeploymentFailureReason::RateLimited,
                $exception->rateLimitResetsAt,
                $exception,
            );
        } catch (ForbiddenException $exception) {
            throw new DeploymentGatewayException(DeploymentFailureReason::Forbidden, previous: $exception);
        } catch (NotFoundException $exception) {
            throw new DeploymentGatewayException(DeploymentFailureReason::NotFound, previous: $exception);
        } catch (FailedActionException|ValidationException $exception) {
            throw new DeploymentGatewayException(DeploymentFailureReason::InvalidRequest, previous: $exception);
        } catch (GuzzleException|TimeoutException $exception) {
            throw new DeploymentGatewayException(DeploymentFailureReason::Unavailable, previous: $exception);
        } catch (DeploymentGatewayException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw new DeploymentGatewayException(DeploymentFailureReason::Unavailable, previous: $exception);
        }
    }
}
