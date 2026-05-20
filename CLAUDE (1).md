# NexStay — AI Assistant Context File

> This file is loaded automatically by Claude and Cursor AI. It gives the assistant full project context so every suggestion is grounded in NexStay's architecture, conventions, and business domain.

---

## What Is NexStay?

NexStay is a **multi-tenant, cloud-native hotel booking and management platform** built with Laravel 11 (PHP 8.3). It serves hotel properties across East Africa and beyond, covering:

- **HBMS** — Hotel Booking & Management System (reservations, rooms, check-in/out, folios, housekeeping, night audit)
- **RMM** — Restaurant Management Module (table management, menu, orders, KDS)
- **BMM** — Bar Management Module (tabs, drink menu, pour control, inventory)
- **DLMM** — Drink Lounge Management Module (lounge reservations, bottle service, ambient POS)
- **Unified Billing** — all charges across every outlet post to a single guest folio
- **Cash & Till Management** — full cash drawer lifecycle, shift reconciliation, over/short reporting
- **Reporting** — executive dashboard, night audit, F&B revenue, cash-up, OTA performance

**Primary markets:** Tanzania (TRA VFD fiscalization required), East Africa broadly.  
**Base currency:** TZS (Tanzanian Shilling) for most properties — always use `brick/money`, never floats.

---

## Tech Stack (Memorise This)

| Layer | Technology |
|---|---|
| Backend | Laravel 11, PHP 8.3, Laravel Octane (Swoole) |
| Database | PostgreSQL 16 — schema-per-tenant via `stancl/tenancy` |
| Cache / Queue | Redis 7 |
| Queue worker | Laravel Horizon |
| WebSocket | Laravel Reverb |
| Search | Meilisearch via Laravel Scout |
| File storage | S3-compatible (MinIO dev / AWS S3 prod) |
| Staff dashboard | Inertia.js + React 19 + TypeScript + Tailwind + Shadcn/ui |
| Guest booking engine | Next.js 15 (App Router) + TypeScript + Tailwind |
| POS + Mobile app | React Native (Expo) + TypeScript |
| KDS screen | React SPA (runs in Chrome kiosk mode) |

---

## Key Packages — Never Suggest Alternatives Unless Asked

| Concern | Package |
|---|---|
| Multi-tenancy | `stancl/tenancy` v3 |
| RBAC | `spatie/laravel-permission` |
| Auth | `laravel/sanctum` |
| Activity log / audit | `spatie/laravel-activitylog` |
| Media | `spatie/laravel-medialibrary` |
| Money arithmetic | `brick/money` — always use this for monetary values |
| PDF | `barryvdh/laravel-dompdf` |
| Excel | `maatwebsite/excel` |
| Feature flags | `laravel/pennant` |
| Backup | `spatie/laravel-backup` |
| MFA | `pragmarx/google2fa-laravel` |

---

## Architecture Pattern

**Modular Monolith** — not microservices. Four domain modules (`HBMS`, `Restaurant`, `Bar`, `Lounge`) live inside a single Laravel app under `app/Domain/`. Modules share infrastructure (DB, cache, queue) but own their models, actions, and services.

```
app/
├── Domain/
│   ├── HBMS/
│   │   ├── Actions/        ← Single-responsibility action classes
│   │   ├── Events/
│   │   ├── Jobs/
│   │   ├── Models/
│   │   └── Services/
│   ├── Restaurant/
│   ├── Bar/
│   ├── Lounge/
│   └── Shared/             ← FolioService, TaxService, PaymentService, etc.
├── Http/
│   ├── Controllers/Api/V1/
│   └── Requests/
└── Providers/
```

---

## Critical Domain Rules (Never Violate)

1. **Money is always `brick/money` Money objects** — never PHP floats, never raw integers without a unit.
2. **No cash payment without an active till session** — `ActiveTillSessionForOutlet` middleware enforces this.
3. **Folio is the single source of financial truth** — all charges (rooms, F&B, cash, card) post to `folio_transactions`. Never store revenue in multiple places.
4. **All financial DB operations use transactions** — `DB::transaction()` on any operation touching folios, payments, or till_movements.
5. **Schema-per-tenant isolation** — never query across tenant schemas. Use `Tenant::current()` context.
6. **Action classes for business logic** — controllers are thin (validate → dispatch action → return response). No business logic in controllers or models.
7. **Soft deletes everywhere** — all domain models use `SoftDeletes`. Hard deletes are forbidden on production data.
8. **All monetary DB columns are `NUMERIC(12,2)`** — never `FLOAT` or `DOUBLE`.
9. **UUID primary keys** — all tables use `gen_random_uuid()`. Never auto-increment IDs.
10. **Night audit is sacred** — never skip the audit lock check before posting backdated transactions.

---

## Multi-Tenancy Context

- Each hotel property = one tenant
- Tenant resolution: subdomain (`zanzibar-pearl.nexstay.io`)
- PostgreSQL schema per tenant: `tenant_{slug}`
- Central schema (`public`): tenant registry, global config, HQ users
- Always run tenant-scoped code inside `$tenant->run(fn() => ...)` or via automatic resolution

---

## Payment Methods

NexStay supports: `cash | card | mobile_money | city_ledger | complimentary | voucher | bank_transfer`

**Cash payments are first-class citizens.** They require:
- An open `till_session` for the outlet
- `received_by` = authenticated staff user
- `cash_tendered` and `cash_change` recorded
- A corresponding `till_movement` entry
- TRA VFD fiscalization (queued, non-blocking)

---

## API Conventions

- Base path: `/api/v1/`
- Auth: Bearer token (Sanctum) for mobile; HTTP-only cookie for Inertia SPA
- Response envelope: `{ data, meta, links }` for collections; `{ data, meta }` for single resource; `{ error: { code, message, details } }` for errors
- Error codes are SCREAMING_SNAKE_CASE strings (e.g. `TILL_NOT_OPEN`, `RESERVATION_CONFLICT`)
- Pagination: cursor-based for transactions/orders; page-based for management tables
- Filtering: `?filter[status]=open&filter[date]=2026-05-15`
- Sorting: `?sort=-created_at`

---

## Database Conventions

- All PKs: `UUID` via `gen_random_uuid()`
- All timestamps: `TIMESTAMPTZ` (not `TIMESTAMP`)
- Soft deletes: `deleted_at TIMESTAMPTZ`
- Monetary columns: `NUMERIC(12,2)` — never FLOAT
- JSONB for flexible config, arrays, snapshots (modifiers, preferences, gateway_response)
- Indexes: every FK column must be indexed; partial indexes for status-filtered queries

---

## Real-Time (Reverb) Channel Naming

| Channel | Events |
|---|---|
| `private-kds.{outlet_id}` | `order.item.fired`, `order.item.bumped` |
| `private-dashboard.{tenant_id}` | `room.status.changed`, `occupancy.updated`, `folio.charge.posted`, `till.closed` |
| `private-table.{outlet_id}` | `table.status.changed` |
| `private-guest.{reservation_id}` | `folio.updated` |

---

## Queue Priority

| Queue | Jobs |
|---|---|
| `high` | KDS push, folio charge posting, payment processing, till alerts |
| `default` | Email/SMS, OTA sync, VFD fiscalization |
| `low` | Report generation, inventory alerts, backup |
| `reports` | Scheduled and on-demand exports (dedicated workers) |

---

## Testing Expectations

- Feature tests for every API endpoint — use `RefreshDatabase`
- Unit tests for every Action and Service class
- Use factories for all test data — never seed production data in tests
- Assert DB state AND response shape in feature tests
- Mock external services (OTA, payment gateways, TRA VFD) — never hit real APIs in tests
- Coverage targets: Unit ≥ 90%, Feature ≥ 80%

---

## Files to Read for Deep Context

| File | Purpose |
|---|---|
| `docs/PRD.md` | Full product requirements — what we're building and why |
| `docs/TRD.md` | Full technical requirements — all architecture decisions |
| `docs/modules/HBMS.md` | Hotel booking module deep-dive |
| `docs/modules/FB.md` | F&B modules (restaurant, bar, lounge) deep-dive |
| `docs/modules/CASH.md` | Cash handling and till management |
| `docs/api/ENDPOINTS.md` | Full API endpoint reference |
| `docs/schema/SCHEMA.md` | Full database schema with annotations |
| `.cursor/rules/laravel.mdc` | Laravel-specific coding rules for Cursor |
| `.cursor/rules/domain.mdc` | Domain/business logic rules for Cursor |
| `.cursor/rules/api.mdc` | API design rules for Cursor |
| `.cursor/rules/database.mdc` | Database rules for Cursor |
| `.cursor/rules/frontend.mdc` | Frontend rules for Cursor |
| `.cursor/rules/testing.mdc` | Testing rules for Cursor |
