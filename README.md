# Laravel Forge Deployments

[![Tests](https://github.com/omaralalwi/laravel-forge-deployments/actions/workflows/tests.yml/badge.svg)](https://github.com/omaralalwi/laravel-forge-deployments/actions/workflows/tests.yml)
[![Latest Stable Version](https://poser.pugx.org/omaralalwi/laravel-forge-deployments/v/stable)](https://packagist.org/packages/omaralalwi/laravel-forge-deployments)
[![License](https://poser.pugx.org/omaralalwi/laravel-forge-deployments/license)](LICENSE)

A secure, authorization-first Laravel dashboard for deploying one configured [Laravel Forge](https://forge.laravel.com) site. It gives trusted application operators deployment status, shared history, actor attribution, and plain-text deployment output without giving them Forge or server credentials.

[العربية](README-AR.md)

## Why this package?

- The host application remains responsible for authentication and decides access through a Laravel Gate.
- Every request, including JSON polling and output, passes through the configured middleware and package authorization middleware.
- The target comes only from trusted server configuration; browser requests cannot choose another server or site.
- A distributed cache lock and a fresh Forge status check prevent overlapping deployments.
- The trigger request is never automatically retried. Ambiguous outcomes fail closed until they can be reconciled.
- A local audit record snapshots the authenticated actor while Forge remains the source of truth for deployment state and output.
- The standalone, responsive dashboard supports light/dark mode and English/Arabic without requiring Bootstrap, Tailwind, Alpine, or a host layout.

## Requirements

- PHP 8.2, 8.3, 8.4, or 8.5
- Laravel 10, 11, 12, or 13 (individual Laravel releases may impose a higher PHP minimum)
- A cache driver that supports atomic locks in environments with more than one application process
- A Laravel Forge API token with only the permissions required to read the configured resources and create deployments

> **Legacy compatibility:** the package retains Laravel 10 and 11 compatibility as requested, but those releases are outside upstream security support and Composer may block installing them when known advisories apply. Prefer a currently supported Laravel release for production. See Laravel's [support policy](https://laravel.com/docs/13.x/releases#support-policy).

## Installation

Install the package:

```bash
composer require omaralalwi/laravel-forge-deployments
```

Publish the configuration and run the package migration:

```bash
php artisan vendor:publish --tag=laravel-forge-deployments-config
php artisan migrate
```

Define the authorization Gate in your application's `App\Providers\AppServiceProvider`. The package intentionally does not guess your roles, guards, permissions, or user model:

```php
use App\Models\User;
use Illuminate\Support\Facades\Gate;

public function boot(): void
{
    Gate::define('manageForgeDeployments', function (User $user): bool {
        return $user->hasPermissionTo('manage deployments');
    });
}
```

This Gate is mandatory. An unauthenticated user, an empty Gate name, an undefined Gate, or a denied Gate all produce HTTP 403.

Add the environment values described below, cache the configuration in deployed environments, and verify read-only connectivity:

```bash
php artisan config:cache
php artisan forge-deployments:check
```

The dashboard is available at `/forge-deployments` by default.

## Forge setup

### 1. Create an API token

Open the [Forge API token page](https://forge.laravel.com/profile/api), choose **Create token**, give the token a clear name and optional expiration, and select the least privileges needed for the configured organization, server, site, and deployments. Forge documents the full process in [API authentication](https://laravel.com/forge/docs/api#authentication).

Copy the token when Forge displays it and store it only in the application's secret environment configuration. Do not commit it, paste it into client-side code, or use a deployment hook URL as the API token.

### 2. Find the organization slug, server ID, and site ID

Set only the token first:

```dotenv
LARAVEL_FORGE_DEPLOYMENTS_API_TOKEN=your-secret-token
```

Then let the package read the identifiers available to that token:

```bash
php artisan forge-deployments:discover
```

You can narrow a large account:

```bash
php artisan forge-deployments:discover --organization=your-organization
php artisan forge-deployments:discover --organization=your-organization --server=123456
```

The command prints organization slugs, server IDs, and site IDs without printing the token. Forge API v2 refers to the application hosted on a server as a **site**, so the value sometimes informally called an “app ID” is configured here as `SITE_ID`. This discovery flow uses the official SDK operations documented under [organizations, servers, and sites](https://laravel.com/forge/docs/sdk#resources).

### 3. Configure the fixed target

```dotenv
LARAVEL_FORGE_DEPLOYMENTS_ENABLED=true
LARAVEL_FORGE_DEPLOYMENTS_API_TOKEN=your-secret-token
LARAVEL_FORGE_DEPLOYMENTS_ORGANIZATION_SLUG=your-organization
LARAVEL_FORGE_DEPLOYMENTS_SERVER_ID=123456
LARAVEL_FORGE_DEPLOYMENTS_SITE_ID=789012
LARAVEL_FORGE_DEPLOYMENTS_TARGET_LABEL="Staging"
LARAVEL_FORGE_DEPLOYMENTS_BRANCH=develop
```

The branch label is informational: Forge runs the branch and deployment script configured on the site. Review those settings in Forge before enabling the dashboard. See Forge's documentation for [deployment scripts, branches, history, and zero-downtime deployment](https://laravel.com/forge/docs/sites/deployments).

The package deliberately uses the `LARAVEL_FORGE_DEPLOYMENTS_` prefix. Forge reserves `FORGE_` variables for values it injects while running deployment scripts, including `FORGE_SERVER_ID` and `FORGE_SITE_ID`; see [Forge runtime environment variables](https://laravel.com/forge/docs/sites/deployments#environment-variables).

## Configuration

Publish `config/forge-deployments.php` to customize:

- `route.prefix`: dashboard URL prefix.
- `route.name_prefix`: route-name prefix.
- `route.middleware`: host middleware applied before package authorization. It defaults to `web` and `auth`; use `auth:admin` or your own middleware when appropriate.
- `authorization.gate`: required Laravel Gate ability.
- `forge.*`: API token, timeouts, and user agent.
- `target.*`: the only organization, server, site, and display branch the dashboard can use.
- `poll_interval_ms`, `history_per_page`, and `ambiguous_request_ttl_seconds`: bounded operational settings.
- `view`: replace the full dashboard while retaining the protected package routes and API payloads.

To publish and customize the built-in UI or translations:

```bash
php artisan vendor:publish --tag=laravel-forge-deployments-views
php artisan vendor:publish --tag=laravel-forge-deployments-translations
```

## Custom authentication guards

The default route middleware is `['web', 'auth']`. For an admin guard, publish the config and change it:

```php
'route' => [
    'prefix' => 'admin/forge-deployments',
    'name_prefix' => 'forge-deployments.',
    'middleware' => ['web', 'auth:admin'],
],
```

The authorization Gate still runs for the authenticated user. Keep both authentication and authorization protection on these routes.

## Actor attribution

By default, the package snapshots the authenticated model class, authentication identifier, `name`, and `email`. It does not add a foreign key to your user table, so audit history survives user deletion and works with custom user models.

For different fields, implement `Omaralalwi\LaravelForgeDeployments\Contracts\ResolvesDeploymentActor` and bind your implementation in the application container.

## Operational behavior

The deployment path is:

```text
authenticated route
  → application Gate
  → atomic cache lock
  → fresh Forge status check
  → local audit row
  → one Forge create-deployment request
  → remote ID/status reconciliation
```

The package does not manage deployment scripts, Git branches, environment files, queues, or server credentials. Forge owns those concerns. Forge's deployment history and output remain authoritative; the local table adds application-user attribution to dashboard-triggered runs.

See [Security model](docs/security-model.md) for the threat boundaries and failure behavior, and [Forge setup](docs/forge-setup.md) for a detailed setup checklist.

## Testing

```bash
composer install
composer format -- --test
composer test
```

The suite has a bootstrap guard and runs only with SQLite `:memory:`.

## Contributing

Contributions are welcome. See [CONTRIBUTING.md](CONTRIBUTING.md).

## License

Laravel Forge Deployments is open-source software licensed under the [MIT license](LICENSE).
