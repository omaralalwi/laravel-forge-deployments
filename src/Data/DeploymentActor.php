<?php

declare(strict_types=1);

namespace Omaralalwi\LaravelForgeDeployments\Data;

final readonly class DeploymentActor
{
    public function __construct(
        public ?string $type,
        public ?string $id,
        public string $name,
        public ?string $email,
    ) {}
}
