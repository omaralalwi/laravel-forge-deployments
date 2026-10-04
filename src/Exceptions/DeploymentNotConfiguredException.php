<?php

declare(strict_types=1);

namespace Omaralalwi\LaravelForgeDeployments\Exceptions;

use RuntimeException;

final class DeploymentNotConfiguredException extends RuntimeException
{
    public function __construct(public readonly string $problem)
    {
        parent::__construct("Forge deployments are {$problem}.");
    }
}
