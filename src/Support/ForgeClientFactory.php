<?php

declare(strict_types=1);

namespace Omaralalwi\LaravelForgeDeployments\Support;

use GuzzleHttp\Client;
use Laravel\Forge\Forge;

final class ForgeClientFactory
{
    public function make(): Forge
    {
        $token = trim((string) config('forge-deployments.forge.api_token', ''));

        $client = new Client([
            'base_uri' => 'https://forge.laravel.com/api/',
            'http_errors' => false,
            'timeout' => max(1, (int) config('forge-deployments.forge.timeout_seconds', 10)),
            'connect_timeout' => max(1, (int) config('forge-deployments.forge.connect_timeout_seconds', 5)),
            'headers' => [
                'Authorization' => 'Bearer '.$token,
                'Accept' => 'application/vnd.api+json',
                'Content-Type' => 'application/vnd.api+json',
                'User-Agent' => (string) config(
                    'forge-deployments.forge.user_agent',
                    'Laravel-Forge-Deployments/Laravel',
                ),
            ],
        ]);

        return new Forge($token, $client);
    }
}
