<?php

declare(strict_types=1);

namespace Omaralalwi\LaravelForgeDeployments\Data;

final readonly class DeploymentHistoryPage
{
    /**
     * @param  list<RemoteDeployment>  $deployments
     */
    public function __construct(
        public array $deployments,
        public ?string $nextCursor,
    ) {}
}
