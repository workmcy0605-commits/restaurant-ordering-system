# Architecture

## Overview

The platform is split into three applications:

1. `apps/admin-api`
2. `apps/admin-portal`
3. `apps/mobile-pwa`

This structure isolates deployment concerns and keeps the admin and customer experiences independent.

## High-Level Flow

```text
admin-portal  --->  admin-api  --->  MySQL
mobile-pwa    --->  backend API ---> MySQL
```

## Responsibilities

### `apps/admin-api`

- Owns admin-facing domain logic
- Exposes JSON APIs for back-office workflows
- Centralizes validation and data mutations for administrative modules

### `apps/admin-portal`

- Operator-facing frontend
- Consumes `admin-api`
- Hosts admin tables, forms, route guards, and dashboards

### `apps/mobile-pwa`

- Customer-facing web mobile experience
- Consumes the configured backend origin through `/api/v1`
- Targets browser-based ordering flows

## Design Goals

- Separate deployable applications
- Clear API contracts
- Focused repositories and cleaner onboarding
- Clean break from the legacy Laravel 12 monolith
