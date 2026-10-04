<?php

declare(strict_types=1);

namespace Omaralalwi\LaravelForgeDeployments\Exceptions;

use Omaralalwi\LaravelForgeDeployments\Enums\DeploymentFailureReason;
use RuntimeException;
use Throwable;

final class DeploymentGatewayException extends RuntimeException
{
    public function __construct(
        public readonly DeploymentFailureReason $reason,
        public readonly ?int $retryAt = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct("Forge deployment request failed: {$reason->value}.", previous: $previous);
    }
}
