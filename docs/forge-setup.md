# Laravel Forge setup guide

This guide configures one fixed Forge site. It does not expose a server or site picker to dashboard users.

## Before you begin

Confirm that:

- your Laravel application is already installed as a site in Forge;
- its repository, deployment branch, and deployment script are correct;
- your application authentication is working;
- you know which users should pass the `manageForgeDeployments` Gate;
- your shared cache supports atomic locks if the application runs on multiple processes or servers.

Forge's [site deployment documentation](https://laravel.com/forge/docs/sites/deployments) explains deployment scripts, branches, deployment history, output, health checks, and zero-downtime macros.

## Create the token

1. Open the [Forge API token page](https://forge.laravel.com/profile/api).
2. Select **Create token**.
3. Use a descriptive name such as the application and environment.
4. Add an expiration date if your credential policy requires rotation.
5. Select only the permissions needed to list the configured resources, read deployment state/history/output, and create a deployment.
6. Store the token immediately in the target environment's secret store.

Forge's official [API authentication guide](https://laravel.com/forge/docs/api#authentication) covers token creation and Bearer authentication. The [API introduction](https://laravel.com/forge/docs/api-reference/introduction) documents the base URL, headers, and response codes used by the official SDK.

## Discover IDs with the package

Add the token without enabling web deployment yet:

```dotenv
LARAVEL_FORGE_DEPLOYMENTS_API_TOKEN=your-secret-token
```

Clear stale configuration if you are not using `config:cache` locally, then run:

```bash
php artisan config:clear
php artisan forge-deployments:discover
```

The three required identifiers are:

| Package setting | Forge meaning | Discovery column |
| --- | --- | --- |
| `ORGANIZATION_SLUG` | Organization slug required by Forge API v2 | Organization `Slug` |
| `SERVER_ID` | Numeric server identifier | `Server ID` |
| `SITE_ID` | Numeric site/application identifier | `Site ID` |

The official [Forge SDK resource guide](https://laravel.com/forge/docs/sdk#resources) shows the same organization → server → site hierarchy and the resource IDs returned by the API.

## Complete the environment block

```dotenv
LARAVEL_FORGE_DEPLOYMENTS_ENABLED=true
LARAVEL_FORGE_DEPLOYMENTS_API_TOKEN=your-secret-token
LARAVEL_FORGE_DEPLOYMENTS_ORGANIZATION_SLUG=your-organization
LARAVEL_FORGE_DEPLOYMENTS_SERVER_ID=123456
LARAVEL_FORGE_DEPLOYMENTS_SITE_ID=789012
LARAVEL_FORGE_DEPLOYMENTS_TARGET_LABEL="Staging"
LARAVEL_FORGE_DEPLOYMENTS_BRANCH=develop
```

Do not rename these variables to start with `FORGE_`. Forge reserves that namespace for values injected into deployment scripts, as documented in [deployment environment variables](https://laravel.com/forge/docs/sites/deployments#environment-variables).

## Verify safely

Run the read-only check before opening access to users:

```bash
php artisan config:cache
php artisan forge-deployments:check
```

The command fetches only the configured site's current deployment status. It does not start a deployment.

Then verify:

1. an unauthenticated request is redirected or rejected by your host authentication middleware;
2. an authenticated user denied by the Gate receives HTTP 403;
3. an authorized user sees the intended target label and branch;
4. the history shown matches the configured Forge site;
5. a controlled staging deployment is accepted once and appears with the initiating user's snapshot;
6. a second trigger is disabled while the first deployment remains active;
7. output is rendered as plain text and contains no application secrets.

## Rotate or revoke a token

Create a replacement token in Forge, update the secret value, run `php artisan config:cache`, and verify with `forge-deployments:check`. Revoke the previous token only after the replacement succeeds. Forge explains token management in the [API documentation](https://laravel.com/forge/docs/api#managing-api-tokens).
