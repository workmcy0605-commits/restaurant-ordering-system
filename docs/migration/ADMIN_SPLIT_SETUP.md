# IFOS Admin Split Setup

This workspace now contains three separate application surfaces:

- `./` - the current Laravel 12 + Blade production web app
- `./admin-api` - the new Laravel 13 admin backend
- `./admin-portal` - the new Soybean Admin frontend

The existing production app remains isolated at the repo root so the admin rollout can happen without forcing an in-place upgrade of the public system.

The frontend scaffold uses the official Soybean Admin Vue codebase (`2.1.0`). If you intended to use the React edition instead, replace `./admin-portal` before building feature work on top of it.

## Local defaults

- `admin-api` root route returns a JSON service descriptor
- `admin-api` health endpoint is `GET /api/v1/health`
- `admin-portal` points to `http://127.0.0.1:8001` for its backend service base URL in local development

## Suggested local run flow

Run the existing Laravel 12 site the way you already do today.

Run the admin API separately from `./admin-api`:

```bash
php artisan serve --host=127.0.0.1 --port=8001
```

Laravel 13 still declares `php ^8.3` in `admin-api/composer.json`. The scaffold was created successfully here, but `composer check-platform-reqs` on this machine still reports host PHP `8.2.27` as below the official requirement. Treat `PHP 8.3+` as the real target runtime for `admin-api`.

Run the admin portal separately from `./admin-portal`:

```bash
pnpm install
pnpm dev
```

## Integration notes

- Keep shared business rules in the API layer instead of duplicating them in the Soybean frontend.
- Introduce shared database access gradually. The safest first step is read-only admin screens, then controlled write modules.
- Use separate deployment pipelines for the root app, `admin-api`, and `admin-portal`.
- The original Soybean git metadata was moved to `admin-portal/.git.upstream`. The `admin-portal/.git.disabled` folder only contains hook files created during dependency installation and is not an active nested repository.
