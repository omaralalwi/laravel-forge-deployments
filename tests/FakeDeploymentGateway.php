<?php

declare(strict_types=1);

namespace Omaralalwi\LaravelForgeDeployments\Tests;

use Omaralalwi\LaravelForgeDeployments\Contracts\DeploymentGateway;
use Omaralalwi\LaravelForgeDeployments\Data\DeploymentHistoryPage;
use Omaralalwi\LaravelForgeDeployments\Data\DeploymentSiteStatus;
use Omaralalwi\LaravelForgeDeployments\Data\DeploymentTarget;
use Omaralalwi\LaravelForgeDeployments\Data\RemoteDeployment;
use Omaralalwi\LaravelForgeDeployments\Enums\DeploymentStatus;
use Throwable;

final class FakeDeploymentGateway implements DeploymentGateway
{
    public DeploymentSiteStatus $current;

    public RemoteDeployment $triggered;

    /** @var list<RemoteDeployment> */
    public array $deployments = [];

    public ?Throwable $triggerException = null;

    public int $triggerCount = 0;

    public string $outputText = "Deployment complete\n";

    public function __construct()
    {
        $this->current = new DeploymentSiteStatus(DeploymentStatus::Ready);
        $this->triggered = new RemoteDeployment(
            id: 999,
            status: DeploymentStatus::Pending,
            branch: 'develop',
        );
    }

    public function currentStatus(DeploymentTarget $target): DeploymentSiteStatus
    {
        return $this->current;
    }

    public function trigger(DeploymentTarget $target): RemoteDeployment
    {
        $this->triggerCount++;

        if ($this->triggerException !== null) {
            throw $this->triggerException;
        }

        return $this->triggered;
    }

    public function find(DeploymentTarget $target, int $deploymentId): RemoteDeployment
    {
        foreach ($this->deployments as $deployment) {
            if ($deployment->id === $deploymentId) {
                return $deployment;
            }
        }

        return $this->triggered;
    }

    public function history(
        DeploymentTarget $target,
        ?string $cursor = null,
        int $perPage = 20,
    ): DeploymentHistoryPage {
        return new DeploymentHistoryPage($this->deployments, null);
    }

    public function output(DeploymentTarget $target, int $deploymentId): string
    {
        return $this->outputText;
    }
}
