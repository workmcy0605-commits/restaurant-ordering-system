# Admin Portal

Soybean Admin frontend for the restaurant ordering platform's back-office experience.

## Stack

- Vue 3
- Vite 7
- TypeScript
- Naive UI
- Soybean Admin

## Purpose

This application is the operator-facing UI for:

- account management
- system settings
- menu management
- future administrative modules

## Local Setup

```powershell
pnpm install
pnpm dev
```

## Environment

Set the admin API base URL in `.env`:

```env
VITE_SERVICE_BASE_URL=http://127.0.0.1:8001
```

## Quality Checks

```powershell
pnpm typecheck
pnpm build
```

## Notes

- This frontend is designed to consume `../admin-api`.
- Confirm the API is running before testing authenticated flows.
