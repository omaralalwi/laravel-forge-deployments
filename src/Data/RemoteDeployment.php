<?php

declare(strict_types=1);

namespace Omaralalwi\LaravelForgeDeployments\Data;

use DateTimeImmutable;
use Omaralalwi\LaravelForgeDeployments\Enums\DeploymentStatus;

final readonly class RemoteDeployment
{
    public function __construct(
        public int $id,
        public DeploymentStatus $status,
        public ?string $type = null,
        public ?string $commitHash = null,
        public ?string $commitAuthor = null,
        public ?string $commitMessage = null,
        public ?string $branch = null,
        public ?DateTimeImmutable $startedAt = null,
        public ?DateTimeImmutable $endedAt = null,
        public ?DateTimeImmutable $createdAt = null,
        public ?DateTimeImmutable $updatedAt = null,
    ) {}
}
