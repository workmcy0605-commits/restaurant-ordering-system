# Testing Guide

## Admin API

```powershell
cd apps/admin-api
php artisan test
php artisan route:list --path=api/v1
```

Health endpoint:

- `GET http://127.0.0.1:8001/api/v1/health`

## Admin Portal

```powershell
cd apps/admin-portal
pnpm typecheck
pnpm build
```

Run locally:

```powershell
pnpm dev
```

## Mobile PWA

```powershell
cd apps/mobile-pwa
npm run build
```

Run locally:

```powershell
npm run dev
```

## Manual Smoke Tests

### Admin Portal

- app boots
- login page loads
- admin routes render
- API calls reach `admin-api`
- CRUD pages load expected data

### Mobile PWA

- app boots
- menu list loads
- menu detail loads
- cart flow works
- checkout succeeds
- order history loads

## Release Checklist

1. Run backend tests
2. Run admin portal typecheck and build
3. Run mobile PWA build
4. Validate environment variables
5. Smoke test critical flows
