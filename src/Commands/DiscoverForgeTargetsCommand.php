<?php

declare(strict_types=1);

namespace Omaralalwi\LaravelForgeDeployments\Commands;

use Illuminate\Console\Command;
use Laravel\Forge\Forge;
use Laravel\Forge\Resources\Organization;
use Laravel\Forge\Resources\Server;
use Laravel\Forge\Resources\Site;
use Throwable;

final class DiscoverForgeTargetsCommand extends Command
{
    protected $signature = 'forge-deployments:discover
        {--organization= : Only list servers and sites in this organization slug}
        {--server= : Only list sites on this server ID}';

    protected $description = 'List the Forge organization slugs, server IDs, and site IDs available to the configured token';

    public function handle(Forge $forge): int
    {
        if (trim((string) config('forge-deployments.forge.api_token')) === '') {
            $this->components->error('Set LARAVEL_FORGE_DEPLOYMENTS_API_TOKEN before running discovery.');

            return self::FAILURE;
        }

        try {
            $organizations = $forge->organizations()->lazy();
            $organizationFilter = trim((string) $this->option('organization'));
            $serverFilter = (int) $this->option('server');
            $organizationRows = [];
            $serverRows = [];
            $siteRows = [];

            foreach ($organizations as $organization) {
                if (! $organization instanceof Organization || $organization->slug === null) {
                    continue;
                }

                if ($organizationFilter !== '' && $organization->slug !== $organizationFilter) {
                    continue;
                }

                $organizationRows[] = [$organization->name, $organization->slug];

                foreach ($forge->servers($organization->slug)->lazy() as $server) {
                    if (! $server instanceof Server || $server->id === null) {
                        continue;
                    }

                    if ($serverFilter > 0 && $server->id !== $serverFilter) {
                        continue;
                    }

                    $serverRows[] = [$organization->slug, $server->name, $server->id, $server->ipAddress];

                    foreach ($forge->serverSites($organization->slug, $server->id)->lazy() as $site) {
                        if (! $site instanceof Site || $site->id === null) {
                            continue;
                        }

                        $siteRows[] = [$organization->slug, $server->id, $site->name, $site->id, $site->url];
                    }
                }
            }

            $this->newLine();
            $this->components->info('Organizations');
            $this->table(['Name', 'Slug'], $organizationRows);
            $this->components->info('Servers');
            $this->table(['Organization', 'Name', 'Server ID', 'IP'], $serverRows);
            $this->components->info('Sites');
            $this->table(['Organization', 'Server ID', 'Name', 'Site ID', 'URL'], $siteRows);

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->components->error('Forge discovery failed: '.$exception->getMessage());

            return self::FAILURE;
        }
    }
}
