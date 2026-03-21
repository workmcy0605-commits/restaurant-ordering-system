# Admin API

Laravel 13 backend for the administrative side of the restaurant ordering platform.

## Responsibilities

- account management APIs
- system setting APIs
- menu management APIs
- admin-focused domain workflows

## Requirements

- PHP 8.3+
- Composer 2
- MySQL 8

## Local Setup

```powershell
copy .env.example .env
composer install
php artisan key:generate
php artisan migrate
php artisan serve --host=127.0.0.1 --port=8001
```

## Health Check

- `GET /api/v1/health`

## Common Commands

```powershell
php artisan route:list --path=api/v1
php artisan test
```

## Notes

- Update `.env` with your real database credentials before testing.
- This service is intended to be consumed by `../admin-portal`.
