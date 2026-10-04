<?php

declare(strict_types=1);

namespace Omaralalwi\LaravelForgeDeployments\Data;

use DateTimeImmutable;
use Omaralalwi\LaravelForgeDeployments\Enums\DeploymentStatus;

final readonly class DeploymentSiteStatus
{
    public function __construct(
        public DeploymentStatus $status,
        public ?DateTimeImmutable $startedAt = null,
    ) {}
}
