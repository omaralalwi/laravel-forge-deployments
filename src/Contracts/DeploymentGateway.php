<?php

declare(strict_types=1);

namespace Omaralalwi\LaravelForgeDeployments\Contracts;

use Omaralalwi\LaravelForgeDeployments\Data\DeploymentHistoryPage;
use Omaralalwi\LaravelForgeDeployments\Data\DeploymentSiteStatus;
use Omaralalwi\LaravelForgeDeployments\Data\DeploymentTarget;
use Omaralalwi\LaravelForgeDeployments\Data\RemoteDeployment;

interface DeploymentGateway
{
    public function currentStatus(DeploymentTarget $target): DeploymentSiteStatus;

    public function trigger(DeploymentTarget $target): RemoteDeployment;

    public function find(DeploymentTarget $target, int $deploymentId): RemoteDeployment;

    public function history(
        DeploymentTarget $target,
        ?string $cursor = null,
        int $perPage = 20,
    ): DeploymentHistoryPage;

    public function output(DeploymentTarget $target, int $deploymentId): string;
}
