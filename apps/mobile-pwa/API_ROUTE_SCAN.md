# IFOS API Route Scan

The mobile-facing API lives in [routes/api.php](/C:/laragon/www/ifos-be/routes/api.php). This scan was done statically from source because the local CLI PHP version is `8.2.27`, while the project requires `>= 8.3.0`, so `php artisan route:list` could not execute here.

## Auth model

- Prefix: `/api/v1`
- Public routes: health, payment webhooks, login
- Protected routes: `auth:api` plus `checkTokenExpiry`
- Auth header: `Authorization: Bearer <access_token>`
- Success envelope: `{ "status": "success", "data": ... }`
- Failure envelope: `{ "status": "fail", "data": { "message": "...", "errorCode": null } }`
- Validation errors can also come back as `{ "message": "Validation failed.", "errors": { ... } }`

## Public routes

| Method | Path | Purpose | Request shape |
| --- | --- | --- | --- |
| GET | `/api/v1/health` | Health check | none |
| POST | `/api/v1/receive-transaction-status-notify` | Payment status webhook | `{ "trx_id", "status", "hash", "amount", "type" }` |
| POST | `/api/v1/verify-payment` | Payment verification callback | `{ "trx_id", "password" }` |
| POST | `/api/v1/login` | User login | `{ "name": string, "password": string, "lang": string }` |

## Protected user routes

| Method | Path | Purpose | Request shape |
| --- | --- | --- | --- |
| GET | `/api/v1/mcash/status` | Check MCash availability | none |
| POST | `/api/v1/user-profile-information` | Read profile summary | none |
| POST | `/api/v1/update-user-language` | Change FE language | `{ "lang": string }` |
| POST | `/api/v1/update-user-profile` | Update nickname/password | `{ "nickname"?: string, "password"?: string, "password_confirmation"?: string }` |
| POST | `/api/v1/update-fcm-token` | Store push token | `{ "fcm_token": string }` |
| GET | `/api/v1/get-order-status` | Read wallet/order status by code | query `code` |
| POST | `/api/v1/logout` | Revoke current token | none |
| GET | `/api/v1/categories` | Load categories, quick dates, meal times | none |
| GET | `/api/v1/categoryList` | Load category list for a day | query `day?` |
| POST | `/api/v1/menu` | Filtered menu grouped by category | `{ "date"?: string, "name"?: string, "menu_category_id"?: number, "meal_time_id"?: number }` |
| POST | `/api/v1/all-menu` | Full menu query with addons | `{ "date"?: string, "name"?: string, "menu_category_id"?: number, "meal_time"?: number|string }` |
| POST | `/api/v1/menu-detail` | Fetch single menu item detail | `{ "code": string }` |
| POST | `/api/v1/add-to-cart` | Cache user cart server-side | app-defined cart payload |
| GET | `/api/v1/get-cart` | Read cached cart | none |
| POST | `/api/v1/order` | Create order from `orders[]` groups | `{ "orders": [{ "date": string, "count"?: number, "items": [{ "menu_item_id": string, "menu_item_option_id"?: number[], "meal_time": string, "remark"?: string }] }] }` |
| POST | `/api/v1/order-list` | Paginated order history | `{ "status"?: string, "start_date"?: string, "end_date"?: string, "page"?: number, "limit"?: number }` |
| POST | `/api/v1/cancel-order` | Cancel by order item id | `{ "order_id": number }` |
| POST | `/api/v1/transaction-history` | Paginated wallet history | `{ "start_date"?: string, "end_date"?: string, "transaction_id"?: string, "transaction_type_id"?: number, "limit"?: number }` |
| POST | `/api/v1/feedback` | Save rating/comment for completed order | `{ "code": string, "rating"?: number, "comment"?: string }` |
| GET | `/api/v1/rating-status` | Check feedback feature toggle | none |
| GET | `/api/v1/order-statuses` | List UI status filters | none |
| GET | `/api/v1/check-delivery` | Detect active delivery today | none |

## Protected notification routes

| Method | Path | Purpose | Request shape |
| --- | --- | --- | --- |
| POST | `/api/v1/notification/list` | Paginated notification list | `{ "page"?: number, "limit"?: number, "start_date"?: string, "end_date"?: string, "status"?: 0\|1\|2 }` |
| POST | `/api/v1/notification/delete-notification` | Delete selected notifications | `{ "notification_id": string[] }` |
| POST | `/api/v1/notification/read-notification` | Mark selected notifications as read | `{ "notification_id": string[] }` |
| POST | `/api/v1/notification/delete-all-notification` | Delete recent notifications | none |
| POST | `/api/v1/notification/read-all-notification` | Mark recent notifications as read | none |

## Separate admin-api service

The repo also contains a second Laravel service in [admin-api](/C:/laragon/www/ifos-be/admin-api) with its own `/api/v1/account-management/*` CRUD routes for roles, admins, companies, branches, restaurants, and users. The generated PWA targets the main mobile API first, because that surface maps cleanly to an installable client app.

## Backend notes that shaped the PWA

- `LoginController` requires exact-case `name`, `password`, and `lang`, then returns `access_token`.
- `ProfileController` exposes `name`, `nickname`, `fe_lang`, `company_name`, `branch_name`, `wallet_type`, and `amount`.
- `MenuController` groups menu items by category and uses menu item `code` as the value needed for ordering and detail lookups.
- `OrderController` accepts a grouped `orders[]` payload. The per-group `count` multiplies every `items[]` row in that group, so the PWA sends one item per group to keep quantities predictable.
- `NotificationController`, `addToCart`, `getCart`, and `getOrderStatus` return non-standard response shapes, so the client has custom normalizers for them.
