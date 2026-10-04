<?php

declare(strict_types=1);

namespace Omaralalwi\LaravelForgeDeployments\Data;

final readonly class DeploymentTarget
{
    public function __construct(
        public string $label,
        public string $organizationSlug,
        public int $serverId,
        public int $siteId,
        public string $branch,
    ) {}
}
