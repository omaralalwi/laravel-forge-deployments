# Security model

## Trust boundaries

- The host Laravel application authenticates users.
- The host application's Gate decides who may use every package route.
- Server-side configuration selects exactly one Forge organization, server, and site.
- The API token exists only in server configuration and outbound Forge API headers.
- Laravel Forge is authoritative for current deployment state, history, and output.
- The local database is authoritative only for the actor snapshot associated with a dashboard request.

## Safety properties

- Every route uses both the configured host middleware and package Gate middleware.
- The client cannot submit an organization slug, server ID, site ID, branch, token, or deploy-hook URL.
- Trigger requests are CSRF protected by Laravel's `web` middleware by default and rate limited.
- The package acquires an atomic lock and checks Forge for an active deployment before triggering.
- It records the local audit row before sending the non-idempotent create request.
- It never retries that create request automatically.
- A timeout or ambiguous response records `unknown` and temporarily blocks another request while operators reconcile the result.
- Deployment output is inserted into the bundled UI with `textContent`, not HTML.
- Logs include internal actor and deployment identifiers but omit the API token and deployment output.

## Host responsibilities

- Define a least-privilege Gate and test allowed and denied users.
- Keep the default authentication middleware or replace it with an equivalent authenticated guard.
- Store and rotate the Forge token through a secret-management process.
- Use a lock-capable shared cache for horizontally scaled applications.
- Keep the Forge deployment script, branch, repository, and environment secure.
- Review deployment output because application deployment scripts may print sensitive values.
- Use HTTPS and the normal Laravel session, CSRF, cookie, and proxy configuration.

## Reporting vulnerabilities

Please follow [SECURITY.md](../SECURITY.md) and do not disclose a suspected vulnerability in a public issue.
