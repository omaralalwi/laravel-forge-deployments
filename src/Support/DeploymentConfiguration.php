<?php

declare(strict_types=1);

namespace Omaralalwi\LaravelForgeDeployments\Support;

use Omaralalwi\LaravelForgeDeployments\Data\DeploymentTarget;
use Omaralalwi\LaravelForgeDeployments\Exceptions\DeploymentNotConfiguredException;

final class DeploymentConfiguration
{
    public function problem(): ?string
    {
        if (! config('forge-deployments.enabled', false)) {
            return 'disabled';
        }

        foreach ([
            config('forge-deployments.forge.api_token'),
            config('forge-deployments.target.organization_slug'),
            config('forge-deployments.target.label'),
            config('forge-deployments.target.branch'),
        ] as $value) {
            if (! is_string($value) || trim($value) === '') {
                return 'incomplete';
            }
        }

        if ((int) config('forge-deployments.target.server_id') <= 0
            || (int) config('forge-deployments.target.site_id') <= 0) {
            return 'incomplete';
        }

        return null;
    }

    public function isReady(): bool
    {
        return $this->problem() === null;
    }

    public function target(): DeploymentTarget
    {
        if ($problem = $this->problem()) {
            throw new DeploymentNotConfiguredException($problem);
        }

        return new DeploymentTarget(
            label: trim((string) config('forge-deployments.target.label')),
            organizationSlug: trim((string) config('forge-deployments.target.organization_slug')),
            serverId: (int) config('forge-deployments.target.server_id'),
            siteId: (int) config('forge-deployments.target.site_id'),
            branch: trim((string) config('forge-deployments.target.branch')),
        );
    }

    public function pollIntervalMilliseconds(): int
    {
        return max(2000, min(10000, (int) config('forge-deployments.poll_interval_ms', 3000)));
    }

    public function historyPerPage(): int
    {
        return max(5, min(50, (int) config('forge-deployments.history_per_page', 20)));
    }

    public function ambiguousRequestTtlSeconds(): int
    {
        return max(30, min(600, (int) config('forge-deployments.ambiguous_request_ttl_seconds', 120)));
    }
}
