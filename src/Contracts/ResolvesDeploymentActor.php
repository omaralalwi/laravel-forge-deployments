<?php

declare(strict_types=1);

namespace Omaralalwi\LaravelForgeDeployments\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;
use Omaralalwi\LaravelForgeDeployments\Data\DeploymentActor;

interface ResolvesDeploymentActor
{
    public function resolve(Authenticatable $user): DeploymentActor;
}
