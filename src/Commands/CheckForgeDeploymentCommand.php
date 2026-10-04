<?php

declare(strict_types=1);

namespace Omaralalwi\LaravelForgeDeployments\Commands;

use Illuminate\Console\Command;
use Omaralalwi\LaravelForgeDeployments\Contracts\DeploymentGateway;
use Omaralalwi\LaravelForgeDeployments\Support\DeploymentConfiguration;
use Throwable;

final class CheckForgeDeploymentCommand extends Command
{
    protected $signature = 'forge-deployments:check';

    protected $description = 'Validate the package configuration and read the configured Forge site status';

    public function handle(DeploymentConfiguration $configuration, DeploymentGateway $gateway): int
    {
        if ($problem = $configuration->problem()) {
            $this->components->error("Forge deployments are {$problem}. Check the published configuration and environment values.");

            return self::FAILURE;
        }

        try {
            $target = $configuration->target();
            $status = $gateway->currentStatus($target);

            $this->components->info('Forge deployment configuration is valid.');
            $this->table(
                ['Target', 'Organization', 'Server ID', 'Site ID', 'Branch', 'Status'],
                [[
                    $target->label,
                    $target->organizationSlug,
                    $target->serverId,
                    $target->siteId,
                    $target->branch,
                    $status->status->value,
                ]],
            );

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->components->error('Forge configuration check failed: '.$exception->getMessage());

            return self::FAILURE;
        }
    }
}
