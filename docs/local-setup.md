# Local Setup

This guide starts the applications in development mode.

## Prerequisites

- PHP 8.3+
- Composer 2
- Node.js 20.19+
- pnpm 10.5+
- npm 10+
- MySQL 8

## 1. Admin API

Directory: `apps/admin-api`

### Install

```powershell
cd apps/admin-api
copy .env.example .env
composer install
php artisan key:generate
```

### Configure

Update `.env` with your local database settings:

```env
APP_URL=http://127.0.0.1:8001
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=restaurant_ordering
DB_USERNAME=root
DB_PASSWORD=
```

### Run

```powershell
php artisan migrate
php artisan serve --host=127.0.0.1 --port=8001
```

Health check:

- `GET http://127.0.0.1:8001/api/v1/health`

## 2. Admin Portal

Directory: `apps/admin-portal`

### Install

```powershell
cd apps/admin-portal
pnpm install
```

### Configure

Set the admin API URL in `.env`:

```env
VITE_SERVICE_BASE_URL=http://127.0.0.1:8001
```

### Run

```powershell
pnpm dev
```

Open the Vite URL printed in the terminal, usually `http://127.0.0.1:5173`.

## 3. Mobile PWA

Directory: `apps/mobile-pwa`

### Install

```powershell
cd apps/mobile-pwa
copy .env.example .env
npm install
```

### Configure

```env
VITE_API_BASE_URL=/api/v1
VITE_BACKEND_ORIGIN=http://127.0.0.1:8001
```

Update `VITE_BACKEND_ORIGIN` if the mobile app should hit another backend URL.

### Run

```powershell
npm run dev
```

Open the Vite URL printed in the terminal, usually `http://127.0.0.1:5174`.

## Suggested Startup Order

1. Start `apps/admin-api`
2. Start `apps/admin-portal`
3. Start `apps/mobile-pwa`

## Verification Commands

### Admin API

```powershell
cd apps/admin-api
php artisan route:list
php artisan test
```

### Admin Portal

```powershell
cd apps/admin-portal
pnpm typecheck
pnpm build
```

### Mobile PWA

```powershell
cd apps/mobile-pwa
npm run build
```
