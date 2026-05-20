# NexStay platform (Laravel)

Multi-tenant hotel operations backend: modular monolith aligned with `docs/NexStay_TRD_v1.0.md`.

## Requirements

- PHP 8.2+ (target 8.3 in production), **Composer**, **PostgreSQL 16** (schema-per-tenant via `stancl/tenancy`), Redis optional for queues.
- Enable the PHP `zip` extension so Composer can expand packages quickly on Windows.

## Quick start (Docker Postgres)

```bash
docker compose up -d postgres
cp .env.example .env
php artisan key:generate
php artisan nexstay:install-demo --migrate-central --seed
php artisan serve
```

Add to your hosts file (optional on Windows if using `localhost` subdomains):

```
127.0.0.1 demo.localhost
```

## Demo credentials

All demo staff accounts share the password from `DEMO_PASSWORD` in `.env` (default **`NexStay2026!`**).

| Role | Email | Use for |
|------|-------|---------|
| **Admin / GM** | `admin@demo.local` | Full access — reservations, folios, users, all HBMS APIs |
| **Front desk** | `frontdesk@demo.local` | Check-in/out, bookings, guests, folio charges |
| **Housekeeper** | `housekeeper@demo.local` | Room status board only |

**Tenant URL:** `http://demo.localhost:8000`  
**API base:** `http://demo.localhost:8000/api/v1`

### Login (get API token)

```bash
curl -s -X POST http://demo.localhost:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"email":"admin@demo.local","password":"NexStay2026!","device_name":"curl"}'
```

Use the returned `data.token` as `Authorization: Bearer {token}` on protected routes.

### Example flows

```bash
# Health (no auth)
curl http://demo.localhost:8000/api/v1/health

# Availability (no auth)
curl "http://demo.localhost:8000/api/v1/availability?check_in=2026-06-01&check_out=2026-06-05&adults=2"

# List reservations (auth required)
curl http://demo.localhost:8000/api/v1/reservations \
  -H "Authorization: Bearer YOUR_TOKEN"
```

## API surface (tenant `demo`)

| Area | Endpoints |
|------|-----------|
| **Auth** | `POST /auth/login`, `POST /auth/logout`, `GET /auth/me` |
| **Availability** | `GET /availability` (public) |
| **Guests** | `GET/POST /guests`, `GET/PATCH /guests/{id}` |
| **Rooms** | `GET /rooms`, `PATCH /rooms/{id}/status` |
| **Room types** | `GET /room-types` |
| **Reservations** | Full lifecycle — list, create, update, check-in, check-out, cancel |
| **Folios** | `GET /folios/{id}`, `POST /folios/{id}/transactions` |
| **Users** | `GET /users` (admin / GM only) |

Central (no tenant): `GET http://localhost:8000/api/v1/health`

## API documentation (Swagger UI)

Interactive OpenAPI docs and “Try it out” requests are available when `API_DOCS_ENABLED=true` (default in local).

| URL | Purpose |
|-----|---------|
| `http://localhost:8000/api/documentation` | Swagger UI (central domain) |
| `http://localhost:8000/docs` | Raw OpenAPI JSON |

1. Start the app and open Swagger UI on the **central** host (`localhost`, not `demo.localhost`).
2. In the server dropdown, choose **Tenant API** and set `tenant` to `demo` (or your property slug).
3. Call **Auth → POST /auth/login** with demo credentials, copy `data.token`, click **Authorize**, paste `Bearer {token}`.
4. Execute protected endpoints from the UI.

Regenerate the spec after changing annotations under `app/OpenApi/`:

```bash
composer openapi:generate
# or: php artisan l5-swagger:generate
```

Set `API_DOCS_ENABLED=false` in production unless you intentionally expose docs.

## Commands

| Command | Description |
|---------|-------------|
| `php artisan nexstay:install-demo --migrate-central --seed` | Create demo tenant, migrate schema, seed hotel + users |
| `php database/scripts/create-demo-tenant.php` | Same as install-demo with seed |
| `php artisan tenants:migrate --tenants=demo` | Migrate demo tenant only |
| `php artisan tenants:seed --tenants=demo` | Re-run demo seeders |

## Project layout

- `app/Domain/HBMS` — rooms, reservations, folios, guests
- `app/Domain/Shared` — cross-module services (`FolioService`, `TaxService`, …)
- `database/migrations` — landlord (central) migrations
- `database/migrations/tenant` — per-tenant schema
- `database/seeders/TenantDatabaseSeeder.php` — roles, permissions, demo property data

Cursor rules live in `.cursor/rules/` (copies of the repo `domain.mdc` / `laravel.mdc`).

## What's next (TRD backlog)

Restaurant, bar, lounge POS, till sessions, night audit, OTA sync, and TRA VFD fiscalization are specified in the TRD but not yet implemented in this codebase.
