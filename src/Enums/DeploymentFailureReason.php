<?php

declare(strict_types=1);

namespace Omaralalwi\LaravelForgeDeployments\Enums;

enum DeploymentFailureReason: string
{
    case Forbidden = 'forbidden';
    case NotFound = 'not_found';
    case RateLimited = 'rate_limited';
    case InvalidRequest = 'invalid_request';
    case Unavailable = 'unavailable';
    case InvalidResponse = 'invalid_response';
}
