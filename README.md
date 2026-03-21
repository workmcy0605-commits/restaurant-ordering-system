# Restaurant Ordering System

A modernized monorepo for the restaurant ordering platform.

This repository is intentionally focused on the new application stack:

- `apps/admin-api` - Laravel 13 backend for administrative workflows
- `apps/admin-portal` - Soybean Admin frontend for back-office operations
- `apps/mobile-pwa` - customer-facing Progressive Web App

The legacy Laravel 12 monolith has been removed from the published repository so the codebase reflects the current platform direction.

## Repository Structure

```text
.
|-- apps/
|   |-- admin-api/
|   |-- admin-portal/
|   `-- mobile-pwa/
`-- docs/
```

## Applications

### `apps/admin-api`

Administrative backend built with Laravel 13.

- PHP 8.3+
- Laravel 13
- MySQL-backed API service
- Default local URL: `http://127.0.0.1:8001`

### `apps/admin-portal`

Back-office frontend built on Soybean Admin.

- Vue 3
- Vite 7
- TypeScript
- Naive UI
- Default local URL: `http://127.0.0.1:5173`

### `apps/mobile-pwa`

Customer-facing ordering experience delivered as a PWA.

- React 18
- React Native Web
- Vite
- Default local URL: `http://127.0.0.1:5174`

## Quick Start

### 1. Start the admin API

```powershell
cd apps/admin-api
copy .env.example .env
composer install
php artisan key:generate
php artisan migrate
php artisan serve --host=127.0.0.1 --port=8001
```

### 2. Start the admin portal

```powershell
cd apps/admin-portal
pnpm install
pnpm dev
```

### 3. Start the mobile PWA

```powershell
cd apps/mobile-pwa
copy .env.example .env
npm install
npm run dev
```

Set `VITE_BACKEND_ORIGIN` in `apps/mobile-pwa/.env` to the backend you want to test against.

## Documentation

- [Local setup](docs/local-setup.md)
- [Architecture](docs/architecture.md)
- [Testing guide](docs/testing.md)
- [Migration notes](docs/migration/ADMIN_SPLIT_SETUP.md)
- [Migration audit](docs/migration/ADMIN_MIGRATION_AUDIT.md)

## Recommended Tooling

- PHP 8.3 or newer
- Composer 2
- Node.js 20.19 or newer
- pnpm 10.5 or newer
- MySQL 8

## Project Status

This repository is prepared for the new platform split:

- Laravel 13 admin API
- Soybean admin portal
- Mobile PWA frontend

For local onboarding, start with [docs/local-setup.md](docs/local-setup.md).
