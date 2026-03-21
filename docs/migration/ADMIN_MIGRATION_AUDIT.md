# Admin Migration Audit

Date: 2026-03-19

## What was tested

Both applications were executed with the local PHP 8.3 binary:

- `C:\laragon\bin\php\php-8.3.16-Win32-vs16-x64\php.exe`

### Existing Laravel 12 application

- `artisan --version` works on PHP 8.3
- `artisan route:list` works on PHP 8.3
- `artisan test` passes, but only the default example tests exist
- `composer check-platform-reqs` on PHP 8.3 still reports a missing `ext-zip` requirement because `phpoffice/phpspreadsheet` is installed in the current app

### New Laravel 13 admin API

- `artisan --version` works on PHP 8.3
- `artisan route:list` works on PHP 8.3
- `artisan test` passes, but only the default example tests exist
- `composer check-platform-reqs` now passes on PHP 8.3 after re-resolving dependencies

## Parity result

The new admin API does **not** work the same as the current project yet.

- Existing Laravel 12 app routes: `316`
- New Laravel 13 admin API routes: `5`

The new admin API is currently only a starter scaffold with a health endpoint and default Laravel routes. None of the business modules from the existing system have been migrated yet.

## Existing feature inventory

Route groups discovered from the current Laravel 12 app:

- `system-setting`: 62 routes
- `order-management`: 48 routes
- `account-management`: 42 routes
- `api/v1`: 31 routes
- `menu-management`: 22 routes
- `delivery-management`: 21 routes
- `oauth`: 15 routes
- `report`: 9 routes
- `wallet-history`: 8 routes
- `credit-adjustment`: 8 routes
- `payment-method`: 7 routes
- `holiday`: 7 routes

Controller inventory from the current app:

- Web controllers in `app/Http/Controllers`: `34`
- API controllers in `app/Http/Controllers/API`: `9`

Route definition files in the current app:

- [routes/web.php](C:\laragon\www\ifos-be\routes\web.php)
- [routes/api.php](C:\laragon\www\ifos-be\routes\api.php)
- [routes/account.php](C:\laragon\www\ifos-be\routes\account.php)
- [routes/menu.php](C:\laragon\www\ifos-be\routes\menu.php)
- [routes/order.php](C:\laragon\www\ifos-be\routes\order.php)
- [routes/delivery.php](C:\laragon\www\ifos-be\routes\delivery.php)
- [routes/system-setting.php](C:\laragon\www\ifos-be\routes\system-setting.php)

## What this means

PHP 8.3 is now a usable runtime for the new `admin-api`, so the migration can proceed on the correct version.

The real work left is application migration, not PHP compatibility:

1. Move the admin-facing domain modules from the current Laravel 12 app into `admin-api`
2. Convert Blade-based admin flows into API endpoints + Soybean frontend screens
3. Preserve the existing mobile/public API behavior where it still needs to stay with the legacy app
4. Add real parity tests, because the current test suites are only framework defaults

## Recommended migration order

1. `account-management`
2. `system-setting`
3. `menu-management`
4. `order-management`
5. `delivery-management`
6. reporting and exports
7. remaining utility endpoints and profile flows

That order keeps the admin migration focused on back-office features first and avoids mixing in every public/mobile concern on day one.
