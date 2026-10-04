<?php

declare(strict_types=1);

return [
    'enabled' => env('LARAVEL_FORGE_DEPLOYMENTS_ENABLED', false),

    'route' => [
        'prefix' => env('LARAVEL_FORGE_DEPLOYMENTS_ROUTE', 'forge-deployments'),
        'name_prefix' => 'forge-deployments.',
        'middleware' => ['web', 'auth'],
    ],

    'authorization' => [
        'gate' => env('LARAVEL_FORGE_DEPLOYMENTS_GATE', 'manageForgeDeployments'),
    ],

    'forge' => [
        'api_token' => env('LARAVEL_FORGE_DEPLOYMENTS_API_TOKEN'),
        'connect_timeout_seconds' => 5,
        'timeout_seconds' => 10,
        'user_agent' => env(
            'LARAVEL_FORGE_DEPLOYMENTS_USER_AGENT',
            'Laravel-Forge-Deployments/'.(env('APP_NAME', 'Laravel')),
        ),
    ],

    'target' => [
        'label' => env('LARAVEL_FORGE_DEPLOYMENTS_TARGET_LABEL', 'Staging'),
        'organization_slug' => env('LARAVEL_FORGE_DEPLOYMENTS_ORGANIZATION_SLUG'),
        'server_id' => env('LARAVEL_FORGE_DEPLOYMENTS_SERVER_ID'),
        'site_id' => env('LARAVEL_FORGE_DEPLOYMENTS_SITE_ID'),
        'branch' => env('LARAVEL_FORGE_DEPLOYMENTS_BRANCH', 'main'),
    ],

    'poll_interval_ms' => 3000,
    'history_per_page' => 20,
    'ambiguous_request_ttl_seconds' => 120,
    'view' => 'laravel-forge-deployments::dashboard',
];
