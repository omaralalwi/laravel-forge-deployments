# Contributing

Thank you for considering a contribution to Laravel Forge Deployments.

## Development setup

1. Fork and clone the repository.
2. Install dependencies with `composer install`.
3. Create a focused branch from `main`.
4. Add or update tests for behavior changes.
5. Run formatting and tests before opening a pull request.

```bash
composer format -- --test
composer test
```

Tests are protected by an early bootstrap guard and must use SQLite with `DB_DATABASE=:memory:`. Never point the package test suite at a persistent database.

## Pull requests

- Keep changes focused and backward compatible where possible.
- Use PSR-12 and the conventions already present in the repository.
- Explain user-visible behavior and security implications clearly.
- Do not include credentials, tokens, deployment output, or private Forge identifiers in fixtures, logs, screenshots, or commits.
- Update the README and changelog when public behavior changes.

## Security issues

Do not open a public issue for a suspected vulnerability. Follow [SECURITY.md](SECURITY.md).
