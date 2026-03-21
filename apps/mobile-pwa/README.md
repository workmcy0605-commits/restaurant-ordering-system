# Mobile PWA

Customer-facing Progressive Web App for browsing menus and placing orders.

## Stack

- React 18
- Vite
- React Native Web
- PWA support

## Local Setup

```powershell
copy .env.example .env
npm install
npm run dev
```

## Environment

Example configuration:

```env
VITE_API_BASE_URL=/api/v1
VITE_BACKEND_ORIGIN=http://127.0.0.1:8001
```

## Build Check

```powershell
npm run build
```

## Notes

- Point `VITE_BACKEND_ORIGIN` at the backend you want to test against.
- This app should be manually smoke tested against real login, menu, cart, and checkout flows before release.
