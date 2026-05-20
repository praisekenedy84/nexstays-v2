# NexStay — Technical Requirements Document
**Version:** 1.0 | **Status:** DRAFT | **Date:** May 2026
**Classification:** Internal — Engineering & Architecture

---

> **Related Document:** NexStay Product Requirements Document (PRD) v1.0
> This TRD translates PRD requirements into concrete technical decisions, architecture patterns,
> API contracts, database schemas, and engineering standards for the NexStay platform.

---

## Table of Contents

1. [Technology Stack Decision](#1-technology-stack-decision)
2. [System Architecture](#2-system-architecture)
3. [Database Decision & Schema Design](#3-database-decision--schema-design)
4. [Laravel Application Structure](#4-laravel-application-structure)
5. [API Design & Contracts](#5-api-design--contracts)
6. [Real-Time Architecture](#6-real-time-architecture)
7. [Authentication & Authorization](#7-authentication--authorization)
8. [Multi-Tenancy Architecture](#8-multi-tenancy-architecture)
9. [Module Technical Specifications](#9-module-technical-specifications)
10. [Integrations](#10-integrations)
11. [Frontend Architecture Decision](#11-frontend-architecture-decision)
12. [Infrastructure & DevOps](#12-infrastructure--devops)
13. [Testing Strategy](#13-testing-strategy)
14. [Security Implementation](#14-security-implementation)
15. [Performance & Scalability](#15-performance--scalability)
16. [Development Standards](#16-development-standards)
17. [Cash Handling & Till Management](#17-cash-handling--till-management)
18. [Appendix — Environment Variables Reference](#18-appendix--environment-variables-reference)

---

## 1. Technology Stack Decision

### 1.1 Backend — Laravel (PHP 8.3)

**Decision: Laravel 11 on PHP 8.3**

Laravel is selected as the primary backend framework. Below is the full rationale against the main alternatives considered.

| Criterion | Laravel (PHP 8.3) | Node.js (Express/Fastify) | Go (Gin/Echo) |
|---|---|---|---|
| **Development speed** | ✅ Excellent — rich ecosystem, expressive ORM | ✅ Good | ⚠️ Slower — more boilerplate |
| **Multi-tenancy support** | ✅ `stancl/tenancy` is mature and battle-tested | ⚠️ Manual implementation | ⚠️ Manual implementation |
| **ORM / complex queries** | ✅ Eloquent + Query Builder handle folio/inventory complexity well | ⚠️ Sequelize/Prisma less mature | ⚠️ No standard ORM |
| **Queue & background jobs** | ✅ Laravel Horizon + Redis — first-class | ✅ BullMQ | ✅ goroutine-native |
| **Real-time / WebSocket** | ✅ Laravel Reverb (native, 2024) | ✅ Socket.io native | ⚠️ Third-party |
| **POS/KDS throughput** | ✅ Sufficient for hotel scale | ✅ Slight edge at extreme concurrency | ✅ Best raw throughput |
| **Auth / RBAC ecosystem** | ✅ Sanctum, Spatie Permission — plug and play | ⚠️ Manual or Passport.js | ⚠️ Manual |
| **PDF / Excel export** | ✅ DomPDF, Maatwebsite Excel — native packages | ⚠️ Library fragmentation | ⚠️ Limited |
| **Team hiring pool (East Africa)** | ✅ PHP/Laravel developers widely available | ✅ Good | ⚠️ Smaller pool |
| **Fiscalization / VFD integration** | ✅ HTTP client + background jobs handle TRA VFD well | ✅ Same | ✅ Same |

**Verdict:** Laravel provides the best balance of development velocity, ecosystem maturity, and team accessibility for NexStay's scale. At hotel-level POS concurrency (typically < 200 simultaneous requests per property), PHP 8.3 with OPcache and Laravel Octane (Swoole) comfortably meets all performance targets.

### 1.2 Core Backend Package Selection

| Concern | Package | Notes |
|---|---|---|
| Multi-tenancy | `stancl/tenancy` v3 | Automatic tenant resolution per subdomain or header |
| RBAC | `spatie/laravel-permission` | Roles + direct permissions; model-level scoping |
| API authentication | `laravel/sanctum` | SPA cookie auth + API token auth |
| Real-time / WebSocket | `laravel/reverb` | Native Laravel WebSocket server (replaces Pusher for self-hosted) |
| Background jobs | `laravel/horizon` | Redis-backed queue with dashboard monitoring |
| Full-text search | `laravel/scout` + `meilisearch` | Guest and reservation search |
| Excel export | `maatwebsite/excel` | XLSX generation for reports |
| PDF generation | `barryvdh/laravel-dompdf` | Folios, receipts, night audit PDF |
| Image handling | `spatie/laravel-medialibrary` | Room photos, menu item images |
| Activity log | `spatie/laravel-activitylog` | Full audit trail on all models |
| Data backup | `spatie/laravel-backup` | Automated DB + storage backups |
| Money/currency | `brick/money` | Immutable money objects, no float arithmetic |
| API rate limiting | Laravel built-in throttle middleware | Per-role configurable limits |
| Feature flags | `laravel/pennant` | Gradual feature rollout per tenant |
| Scheduled tasks | Laravel Scheduler | Night audit, report generation, OTA sync |

### 1.3 Performance Booster — Laravel Octane

For POS-heavy deployments, **Laravel Octane with Swoole** is enabled to run PHP as a persistent process, eliminating framework bootstrap overhead on every request.

```
- Swoole coroutines handle concurrent WebSocket + HTTP on a single process pool
- Octane + Swoole benchmarks: ~10x throughput improvement over traditional PHP-FPM
- Required for KDS real-time order push at scale (> 50 tables simultaneously)
```

Octane is **optional at development time** but **required in production** for any property with > 100 concurrent POS sessions.

---

## 2. System Architecture

### 2.1 Architecture Pattern — Modular Monolith

**Decision: Modular Monolith (not microservices)**

For NexStay v1.0, a **modular monolith** is chosen over a microservices architecture. The reasoning:

- Hotel-scale traffic does not justify the operational overhead of a distributed system
- A single Laravel application with well-defined internal module boundaries provides team velocity without sacrificing future extractability
- Modules can be extracted into independent services in v2.x if a specific module needs independent scaling (e.g. a high-traffic OTA channel manager for a large chain)
- Shared database transactions across modules (folio posting, night audit) are dramatically simpler in a monolith

```
┌─────────────────────────────────────────────────────────────────────┐
│                        NexStay Application                          │
│                                                                     │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────┐           │
│  │  HBMS    │  │Restaurant│  │   Bar    │  │  Lounge  │  Modules  │
│  │  Module  │  │  Module  │  │  Module  │  │  Module  │           │
│  └────┬─────┘  └────┬─────┘  └────┬─────┘  └────┬─────┘           │
│       │              │              │              │                 │
│  ┌────▼──────────────▼──────────────▼──────────────▼─────┐         │
│  │              Shared Domain Services                     │         │
│  │   FolioService │ BillingService │ TaxService │ AuthService │     │
│  └────────────────────────────────────────────────────────┘         │
│                                                                     │
│  ┌─────────────────────────────────────────────────────────┐        │
│  │              Infrastructure Layer                        │        │
│  │  Database │ Cache │ Queue │ Storage │ Mailer │ Events   │        │
│  └─────────────────────────────────────────────────────────┘        │
└─────────────────────────────────────────────────────────────────────┘
```

### 2.2 Request Flow

```
Guest / Staff Client
        │
        ▼
  [Nginx / Caddy]          ← TLS termination, static assets, rate limiting
        │
        ▼
  [Laravel Octane]         ← Swoole HTTP server, persistent application
        │
        ├─── API Routes ──► [API Controllers] ──► [Services] ──► [Models/DB]
        │
        ├─── Broadcasting ► [Laravel Reverb] ──► WebSocket clients (KDS, dashboard)
        │
        └─── Queued Jobs ─► [Laravel Horizon] ─► [Redis Queue] ─► Workers
                                                        │
                                                  OTA sync, night audit,
                                                  email/SMS, VFD posting
```

### 2.3 Infrastructure Components

| Component | Technology | Purpose |
|---|---|---|
| Web Server | Nginx or Caddy | TLS termination, reverse proxy to Octane |
| Application | Laravel 11 + Octane (Swoole) | Core business logic |
| Database | PostgreSQL 16 | Primary data store (see Section 3) |
| Cache / Sessions | Redis 7 | Session store, cache, queue backend |
| Queue Worker | Laravel Horizon | Background jobs, OTA sync, reports |
| WebSocket Server | Laravel Reverb | Real-time KDS, dashboard, notifications |
| Search | Meilisearch | Guest search, reservation lookup |
| File Storage | S3-compatible (MinIO self-hosted or AWS S3) | Room photos, menu images, report PDFs |
| Email | SMTP / SendGrid | Transactional emails |
| SMS | Africa's Talking API | Guest SMS (East Africa) |
| Monitoring | Laravel Telescope (dev) + Sentry (prod) | Error tracking, query profiling |
| Metrics | Prometheus + Grafana | Infrastructure and app performance metrics |

---

## 3. Database Decision & Schema Design

### 3.1 PostgreSQL vs. MySQL — Decision Matrix

| Feature | PostgreSQL 16 | MySQL 8.x / MariaDB 11 |
|---|---|---|
| **JSON / JSONB support** | ✅ JSONB with full indexing — critical for flexible menu modifier storage, tax config, and OTA payload logging | ⚠️ JSON support exists but slower indexing |
| **Advanced data types** | ✅ Arrays, hstore, ranges, UUID native | ⚠️ Limited — workarounds needed |
| **Window functions** | ✅ Full support — needed for RevPAR rolling averages, occupancy trend queries | ✅ Supported since MySQL 8 |
| **Full ACID + MVCC** | ✅ Best-in-class — no locking on reads during night audit | ✅ InnoDB MVCC is good but inferior under high write concurrency |
| **Partial indexes** | ✅ e.g. `WHERE status = 'active'` — efficient folio and reservation filtering | ❌ Not supported |
| **CHECK constraints** | ✅ Enforced at DB level | ⚠️ Parsed but not enforced in older MySQL |
| **Enum types** | ✅ Native, reusable | ⚠️ Column-level only, messy to alter |
| **Multi-tenancy schema isolation** | ✅ Schema-per-tenant is a native PostgreSQL pattern (used by `stancl/tenancy`) | ⚠️ Database-per-tenant only at MySQL level |
| **Concurrent writes (POS load)** | ✅ Row-level locking, better under mixed read/write | ✅ Good, but write contention higher |
| **Laravel Eloquent compatibility** | ✅ Full | ✅ Full |
| **Hosting availability** | ✅ Available on all major cloud providers | ✅ Same |
| **Familiarity in Tanzania/East Africa** | ⚠️ MySQL more common locally | ✅ Very common |

**Decision: PostgreSQL 16**

PostgreSQL's JSONB support (critical for flexible menu modifiers, OTA payloads, and tax rule config), schema-per-tenant multi-tenancy, and partial indexes give it a decisive technical edge for NexStay. The marginal familiarity advantage of MySQL is outweighed by PostgreSQL's capabilities.

> **Note for team:** If existing team members are MySQL-native, the transition is smooth — Eloquent ORM abstracts most differences. Key areas to learn: `jsonb` operators, schema-based tenancy, and `::cast` syntax in raw queries.

### 3.2 Multi-Tenancy Database Strategy

`stancl/tenancy` is configured in **multi-database mode using PostgreSQL schemas** (not separate databases). Each property (tenant) gets its own PostgreSQL schema (e.g. `tenant_zanzibar_pearl`, `tenant_serengeti_lodge`).

```
PostgreSQL Instance
├── Schema: public          ← Central: tenant registry, billing, global config
├── Schema: tenant_abc123   ← Property A: all NexStay tables
├── Schema: tenant_def456   ← Property B: all NexStay tables
└── Schema: tenant_ghi789   ← Property C: all NexStay tables
```

**Benefits:**
- Complete data isolation between properties
- Easy per-tenant backup and restore
- Single database instance reduces operational cost
- Schema can be cloned for new tenant provisioning

### 3.3 Core Schema Design

Below are the key tables per module. All tables include `id` (UUID), `created_at`, `updated_at`, and `deleted_at` (soft deletes) unless noted.

---

#### 3.3.1 HBMS — Reservations & Rooms

```sql
-- Room types defined by the property
CREATE TABLE room_types (
    id          UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    name        VARCHAR(100) NOT NULL,          -- e.g. "Deluxe Ocean View"
    code        VARCHAR(20)  NOT NULL UNIQUE,   -- e.g. "DOV"
    description TEXT,
    max_adults  SMALLINT NOT NULL DEFAULT 2,
    max_children SMALLINT NOT NULL DEFAULT 1,
    base_rate   NUMERIC(12,2) NOT NULL,         -- in property base currency
    amenities   JSONB,                          -- ["WiFi","Mini Bar","Bathrobe"]
    photos      JSONB,                          -- array of S3 keys
    created_at  TIMESTAMPTZ DEFAULT now(),
    updated_at  TIMESTAMPTZ DEFAULT now()
);

-- Individual room inventory
CREATE TABLE rooms (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    room_type_id    UUID NOT NULL REFERENCES room_types(id),
    room_number     VARCHAR(10) NOT NULL UNIQUE,
    floor           SMALLINT,
    status          VARCHAR(30) NOT NULL DEFAULT 'vacant_clean',
    -- ENUM: vacant_clean | vacant_dirty | occupied | out_of_order | maintenance
    is_smoking      BOOLEAN DEFAULT false,
    notes           TEXT,
    created_at      TIMESTAMPTZ DEFAULT now(),
    updated_at      TIMESTAMPTZ DEFAULT now()
);

-- Guest profiles (shared across stays)
CREATE TABLE guests (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    first_name      VARCHAR(100) NOT NULL,
    last_name       VARCHAR(100) NOT NULL,
    email           VARCHAR(255) UNIQUE,
    phone           VARCHAR(30),
    nationality     CHAR(2),                   -- ISO 3166-1 alpha-2
    id_type         VARCHAR(30),               -- passport | national_id | drivers_license
    id_number       VARCHAR(100),
    date_of_birth   DATE,
    preferences     JSONB,                     -- {"pillow":"soft","floor":"high","diet":"halal"}
    vip_level       SMALLINT DEFAULT 0,        -- 0=regular, 1=silver, 2=gold, 3=platinum
    created_at      TIMESTAMPTZ DEFAULT now(),
    updated_at      TIMESTAMPTZ DEFAULT now(),
    deleted_at      TIMESTAMPTZ
);

-- Reservations
CREATE TABLE reservations (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    booking_ref     VARCHAR(20) NOT NULL UNIQUE,   -- e.g. NXS-2026-00042
    guest_id        UUID NOT NULL REFERENCES guests(id),
    room_id         UUID REFERENCES rooms(id),     -- NULL until assigned
    room_type_id    UUID NOT NULL REFERENCES room_types(id),
    status          VARCHAR(30) NOT NULL DEFAULT 'confirmed',
    -- ENUM: inquiry | confirmed | checked_in | checked_out | cancelled | no_show
    check_in_date   DATE NOT NULL,
    check_out_date  DATE NOT NULL,
    adults          SMALLINT NOT NULL DEFAULT 1,
    children        SMALLINT NOT NULL DEFAULT 0,
    rate_plan_id    UUID REFERENCES rate_plans(id),
    daily_rate      NUMERIC(12,2) NOT NULL,
    source          VARCHAR(50),               -- direct | booking_com | expedia | walk_in
    ota_ref         VARCHAR(100),              -- OTA confirmation number
    special_requests TEXT,
    deposit_amount  NUMERIC(12,2) DEFAULT 0,
    created_at      TIMESTAMPTZ DEFAULT now(),
    updated_at      TIMESTAMPTZ DEFAULT now(),
    deleted_at      TIMESTAMPTZ
);

-- Rate plans
CREATE TABLE rate_plans (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    name            VARCHAR(100) NOT NULL,
    code            VARCHAR(20) NOT NULL UNIQUE,
    type            VARCHAR(30),               -- rack | corporate | package | ota | promo
    currency        CHAR(3) NOT NULL DEFAULT 'USD',
    is_active       BOOLEAN DEFAULT true,
    valid_from      DATE,
    valid_to        DATE,
    restrictions    JSONB,                     -- min_stay, advance_purchase rules
    created_at      TIMESTAMPTZ DEFAULT now(),
    updated_at      TIMESTAMPTZ DEFAULT now()
);

-- Rate plan pricing by room type and date
CREATE TABLE rate_plan_prices (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    rate_plan_id    UUID NOT NULL REFERENCES rate_plans(id),
    room_type_id    UUID NOT NULL REFERENCES room_types(id),
    date            DATE NOT NULL,
    price           NUMERIC(12,2) NOT NULL,
    UNIQUE (rate_plan_id, room_type_id, date)
);

-- Folio (financial account per stay)
CREATE TABLE folios (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    reservation_id  UUID NOT NULL REFERENCES reservations(id),
    folio_number    VARCHAR(20) NOT NULL UNIQUE,
    status          VARCHAR(20) DEFAULT 'open',  -- open | closed | disputed
    currency        CHAR(3) NOT NULL DEFAULT 'USD',
    pre_auth_amount NUMERIC(12,2) DEFAULT 0,
    settled_amount  NUMERIC(12,2) DEFAULT 0,
    created_at      TIMESTAMPTZ DEFAULT now(),
    updated_at      TIMESTAMPTZ DEFAULT now()
);

-- All charges on a folio (rooms, F&B, extras)
CREATE TABLE folio_transactions (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    folio_id        UUID NOT NULL REFERENCES folios(id),
    transaction_type VARCHAR(30) NOT NULL,
    -- ENUM: room_charge | restaurant | bar | lounge | minibar | phone | laundry
    --       | payment | discount | tax | deposit | adjustment
    description     VARCHAR(255) NOT NULL,
    amount          NUMERIC(12,2) NOT NULL,    -- negative = credit/payment
    tax_amount      NUMERIC(12,2) DEFAULT 0,
    tax_code        VARCHAR(10),               -- A | B | C | D (TRA codes)
    reference_id    UUID,                      -- links to order_id, payment_id, etc.
    reference_type  VARCHAR(50),               -- App\Models\Order, App\Models\Payment
    posted_by       UUID REFERENCES users(id),
    posted_at       TIMESTAMPTZ DEFAULT now(),
    voided_at       TIMESTAMPTZ,
    void_reason     TEXT,
    created_at      TIMESTAMPTZ DEFAULT now()
);

-- Indexes for folio performance
CREATE INDEX idx_folio_transactions_folio_id ON folio_transactions(folio_id);
CREATE INDEX idx_folio_transactions_type ON folio_transactions(transaction_type);
CREATE INDEX idx_folio_transactions_posted_at ON folio_transactions(posted_at);
CREATE INDEX idx_reservations_status ON reservations(status);
CREATE INDEX idx_reservations_checkin ON reservations(check_in_date);
CREATE INDEX idx_rooms_status ON rooms(status);
```

---

#### 3.3.2 F&B Shared — Menu & Orders

```sql
-- Outlet: restaurant, bar, lounge
CREATE TABLE outlets (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    name            VARCHAR(100) NOT NULL,
    type            VARCHAR(30) NOT NULL,      -- restaurant | bar | lounge
    floor_plan      JSONB,                     -- table positions for layout builder
    is_active       BOOLEAN DEFAULT true,
    settings        JSONB,                     -- KDS printers, tax overrides, etc.
    created_at      TIMESTAMPTZ DEFAULT now(),
    updated_at      TIMESTAMPTZ DEFAULT now()
);

-- Menu categories
CREATE TABLE menu_categories (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    outlet_id       UUID NOT NULL REFERENCES outlets(id),
    name            VARCHAR(100) NOT NULL,
    display_order   SMALLINT DEFAULT 0,
    is_active       BOOLEAN DEFAULT true,
    created_at      TIMESTAMPTZ DEFAULT now()
);

-- Menu items
CREATE TABLE menu_items (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    category_id     UUID NOT NULL REFERENCES menu_categories(id),
    name            VARCHAR(150) NOT NULL,
    description     TEXT,
    price           NUMERIC(12,2) NOT NULL,
    cost            NUMERIC(12,2),             -- COGS for food cost % reporting
    sku             VARCHAR(50),
    allergens       JSONB,                     -- ["gluten","nuts","dairy"]
    tags            JSONB,                     -- ["vegan","halal","signature"]
    photo_key       VARCHAR(255),              -- S3 key
    is_available    BOOLEAN DEFAULT true,      -- 86'd flag
    sort_order      SMALLINT DEFAULT 0,
    created_at      TIMESTAMPTZ DEFAULT now(),
    updated_at      TIMESTAMPTZ DEFAULT now(),
    deleted_at      TIMESTAMPTZ
);

-- Modifier groups (e.g. "Cooking Preference", "Add-ons")
CREATE TABLE modifier_groups (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    menu_item_id    UUID NOT NULL REFERENCES menu_items(id),
    name            VARCHAR(100) NOT NULL,
    is_required     BOOLEAN DEFAULT false,
    min_select      SMALLINT DEFAULT 0,
    max_select      SMALLINT DEFAULT 1
);

-- Individual modifier options
CREATE TABLE modifiers (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    group_id        UUID NOT NULL REFERENCES modifier_groups(id),
    name            VARCHAR(100) NOT NULL,     -- "Medium Rare", "Extra Cheese"
    price_delta     NUMERIC(10,2) DEFAULT 0,   -- additional charge
    is_available    BOOLEAN DEFAULT true
);

-- Tables (physical seating)
CREATE TABLE outlet_tables (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    outlet_id       UUID NOT NULL REFERENCES outlets(id),
    table_number    VARCHAR(20) NOT NULL,
    capacity        SMALLINT NOT NULL,
    section         VARCHAR(50),               -- "Indoor" | "Terrace" | "VIP Booth"
    status          VARCHAR(30) DEFAULT 'available',
    -- ENUM: available | occupied | reserved | cleaning
    position_x      NUMERIC(6,2),             -- for floor plan rendering
    position_y      NUMERIC(6,2),
    created_at      TIMESTAMPTZ DEFAULT now(),
    updated_at      TIMESTAMPTZ DEFAULT now()
);

-- Orders (per table / per tab)
CREATE TABLE orders (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    outlet_id       UUID NOT NULL REFERENCES outlets(id),
    table_id        UUID REFERENCES outlet_tables(id),
    folio_id        UUID REFERENCES folios(id),  -- NULL for walk-in non-hotel guests
    order_number    VARCHAR(20) NOT NULL UNIQUE,
    status          VARCHAR(30) DEFAULT 'open',
    -- ENUM: open | sent_to_kitchen | partially_served | served | closed | voided
    covers          SMALLINT DEFAULT 1,
    waiter_id       UUID REFERENCES users(id),
    notes           TEXT,
    subtotal        NUMERIC(12,2) DEFAULT 0,
    tax_amount      NUMERIC(12,2) DEFAULT 0,
    discount_amount NUMERIC(12,2) DEFAULT 0,
    total           NUMERIC(12,2) DEFAULT 0,
    opened_at       TIMESTAMPTZ DEFAULT now(),
    closed_at       TIMESTAMPTZ,
    created_at      TIMESTAMPTZ DEFAULT now(),
    updated_at      TIMESTAMPTZ DEFAULT now()
);

-- Order line items
CREATE TABLE order_items (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    order_id        UUID NOT NULL REFERENCES orders(id),
    menu_item_id    UUID NOT NULL REFERENCES menu_items(id),
    quantity        SMALLINT NOT NULL DEFAULT 1,
    unit_price      NUMERIC(12,2) NOT NULL,    -- price at time of order
    modifiers       JSONB,                     -- snapshot of selected modifiers + prices
    notes           TEXT,                      -- e.g. "no onions"
    course          SMALLINT DEFAULT 1,        -- course firing order
    status          VARCHAR(30) DEFAULT 'pending',
    -- ENUM: pending | sent | preparing | ready | served | voided
    sent_to_kds_at  TIMESTAMPTZ,
    prepared_at     TIMESTAMPTZ,
    served_at       TIMESTAMPTZ,
    created_at      TIMESTAMPTZ DEFAULT now(),
    updated_at      TIMESTAMPTZ DEFAULT now()
);

CREATE INDEX idx_orders_outlet_status ON orders(outlet_id, status);
CREATE INDEX idx_orders_folio ON orders(folio_id) WHERE folio_id IS NOT NULL;
CREATE INDEX idx_order_items_order ON order_items(order_id);
CREATE INDEX idx_order_items_status ON order_items(status);
```

---

#### 3.3.3 Inventory

```sql
-- Stock items (ingredients, bottles, supplies)
CREATE TABLE stock_items (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    outlet_id       UUID REFERENCES outlets(id), -- NULL = property-wide store
    name            VARCHAR(150) NOT NULL,
    category        VARCHAR(50),               -- beverage | food | supply
    unit            VARCHAR(20) NOT NULL,       -- ml | g | bottle | case | piece
    reorder_level   NUMERIC(10,3) DEFAULT 0,
    current_stock   NUMERIC(10,3) DEFAULT 0,
    cost_per_unit   NUMERIC(12,4),
    supplier_id     UUID REFERENCES suppliers(id),
    created_at      TIMESTAMPTZ DEFAULT now(),
    updated_at      TIMESTAMPTZ DEFAULT now()
);

-- Recipe ingredients linking menu items to stock
CREATE TABLE recipe_ingredients (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    menu_item_id    UUID NOT NULL REFERENCES menu_items(id),
    stock_item_id   UUID NOT NULL REFERENCES stock_items(id),
    quantity        NUMERIC(10,4) NOT NULL,    -- amount consumed per serve
    unit            VARCHAR(20) NOT NULL
);

-- Stock movements (in / out / adjustment)
CREATE TABLE stock_movements (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    stock_item_id   UUID NOT NULL REFERENCES stock_items(id),
    movement_type   VARCHAR(30) NOT NULL,
    -- ENUM: purchase | consumption | wastage | adjustment | transfer_in | transfer_out
    quantity        NUMERIC(10,3) NOT NULL,    -- positive = in, negative = out
    reference_id    UUID,                      -- order_id, purchase_order_id, etc.
    notes           TEXT,
    performed_by    UUID REFERENCES users(id),
    created_at      TIMESTAMPTZ DEFAULT now()
);
```

---

#### 3.3.4 Payments & Tax

```sql
-- Payment records
-- Covers ALL payment methods: online gateways, mobile money, AND staff-recorded cash.
-- Cash payments are recorded manually by staff at point of sale or reception.
-- gateway and gateway_ref are NULL for cash, city_ledger, and complimentary payments.
CREATE TABLE payments (
    id               UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    folio_id         UUID REFERENCES folios(id),
    order_id         UUID REFERENCES orders(id),
    till_session_id  UUID REFERENCES till_sessions(id), -- NULL for online/gateway payments
    amount           NUMERIC(12,2) NOT NULL,
    currency         CHAR(3) NOT NULL,
    method           VARCHAR(30) NOT NULL,
    -- ENUM: cash | card | mobile_money | city_ledger | complimentary | voucher | bank_transfer
    gateway          VARCHAR(50),               -- stripe | flutterwave | azampay | NULL for cash
    gateway_ref      VARCHAR(255),              -- gateway transaction ID; NULL for cash
    gateway_response JSONB,                     -- full gateway payload; NULL for cash
    cash_tendered    NUMERIC(12,2),             -- amount given by guest (cash only)
    cash_change      NUMERIC(12,2),             -- change returned to guest (cash only)
    received_by      UUID REFERENCES users(id), -- staff member who accepted payment
    receipt_number   VARCHAR(50),               -- internal receipt number (all methods)
    notes            TEXT,                      -- staff notes e.g. "Guest paid in USD, converted at 2580"
    status           VARCHAR(20) DEFAULT 'pending',
    -- ENUM: pending | authorized | captured | failed | refunded | voided
    fiscalized_at    TIMESTAMPTZ,               -- TRA VFD timestamp
    fiscal_ref       VARCHAR(100),              -- TRA VFD receipt number
    created_at       TIMESTAMPTZ DEFAULT now(),
    updated_at       TIMESTAMPTZ DEFAULT now()
);

-- Till sessions: tracks a cash drawer opened and closed per shift per outlet
CREATE TABLE till_sessions (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    outlet_id       UUID REFERENCES outlets(id),  -- NULL = front desk / reception
    opened_by       UUID NOT NULL REFERENCES users(id),
    closed_by       UUID REFERENCES users(id),
    float_amount    NUMERIC(12,2) NOT NULL DEFAULT 0, -- opening cash float
    currency        CHAR(3) NOT NULL DEFAULT 'USD',
    status          VARCHAR(20) DEFAULT 'open',       -- open | closed | reconciled
    opened_at       TIMESTAMPTZ DEFAULT now(),
    closed_at       TIMESTAMPTZ,
    -- Closing declaration (entered by staff during cash-up)
    declared_cash   NUMERIC(12,2),             -- what staff counted in the drawer
    system_cash     NUMERIC(12,2),             -- what system expects based on transactions
    over_short      NUMERIC(12,2)              -- declared_cash - system_cash (+ = over, - = short)
        GENERATED ALWAYS AS (declared_cash - system_cash) STORED,
    manager_notes   TEXT,                      -- manager comments on discrepancy
    created_at      TIMESTAMPTZ DEFAULT now(),
    updated_at      TIMESTAMPTZ DEFAULT now()
);

-- Till movements: every cash-in and cash-out event against a till session
-- Includes payments, refunds, floats, paid-outs, and bank drops
CREATE TABLE till_movements (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    till_session_id UUID NOT NULL REFERENCES till_sessions(id),
    movement_type   VARCHAR(30) NOT NULL,
    -- ENUM: opening_float | cash_payment | cash_refund | paid_out | bank_drop
    --       | tip_collected | foreign_exchange | closing_float
    amount          NUMERIC(12,2) NOT NULL,     -- positive = cash IN, negative = cash OUT
    currency        CHAR(3) NOT NULL,
    reference_id    UUID,                       -- payment_id, order_id, or folio_id
    reference_type  VARCHAR(50),
    description     VARCHAR(255) NOT NULL,      -- human-readable reason
    performed_by    UUID NOT NULL REFERENCES users(id),
    approved_by     UUID REFERENCES users(id),  -- required for paid_out and bank_drop
    created_at      TIMESTAMPTZ DEFAULT now()
);

-- Foreign currency log: when guests pay in a currency other than the property base
CREATE TABLE foreign_currency_transactions (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    payment_id      UUID NOT NULL REFERENCES payments(id),
    foreign_currency CHAR(3) NOT NULL,          -- e.g. USD, EUR, GBP
    foreign_amount  NUMERIC(12,2) NOT NULL,     -- amount in foreign currency
    exchange_rate   NUMERIC(12,6) NOT NULL,     -- rate applied at time of transaction
    base_currency   CHAR(3) NOT NULL,           -- property base currency e.g. TZS
    base_amount     NUMERIC(12,2) NOT NULL,     -- converted amount posted to folio
    rate_source     VARCHAR(50),                -- manual | xe_api | central_bank
    recorded_by     UUID NOT NULL REFERENCES users(id),
    created_at      TIMESTAMPTZ DEFAULT now()
);

-- Tax configuration per property
CREATE TABLE tax_configs (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    name            VARCHAR(100) NOT NULL,     -- "VAT", "Accommodation Levy"
    code            VARCHAR(10) NOT NULL,       -- A | B | C | D (TRA codes)
    rate            NUMERIC(6,4) NOT NULL,      -- e.g. 0.18 for 18%
    applies_to      JSONB,                      -- ["room_charge","restaurant","bar"]
    is_inclusive    BOOLEAN DEFAULT false,
    is_active       BOOLEAN DEFAULT true,
    created_at      TIMESTAMPTZ DEFAULT now()
);
```

> **Key design notes:**
> - `received_by` on `payments` is mandatory for cash — it creates a clear accountability trail linking every cash payment to the staff member who handled it.
> - `till_sessions` must be opened before any cash payment can be recorded in that outlet. The system enforces this at the application layer.
> - `till_movements` is the ledger for the physical cash drawer — every cent in or out is a record. This is what the system uses to compute `system_cash` during cash-up.
> - Foreign currency cash payments flow through `foreign_currency_transactions` before posting the converted amount to the folio, ensuring the folio always carries values in the property's base currency.

---

## 4. Laravel Application Structure

### 4.1 Directory Structure

```
nexstay/
├── app/
│   ├── Console/
│   │   └── Commands/
│   │       ├── RunNightAudit.php
│   │       ├── SyncOtaChannels.php
│   │       └── SendLowStockAlerts.php
│   │
│   ├── Domain/                         ← Core business modules
│   │   ├── HBMS/                       ← Hotel Booking & Management
│   │   │   ├── Actions/
│   │   │   │   ├── CheckInGuest.php
│   │   │   │   ├── CheckOutGuest.php
│   │   │   │   ├── CreateReservation.php
│   │   │   │   └── RunNightAudit.php
│   │   │   ├── Events/
│   │   │   │   ├── GuestCheckedIn.php
│   │   │   │   └── RoomStatusChanged.php
│   │   │   ├── Jobs/
│   │   │   │   ├── SyncAvailabilityToOta.php
│   │   │   │   └── ProcessNoShows.php
│   │   │   ├── Models/
│   │   │   │   ├── Reservation.php
│   │   │   │   ├── Room.php
│   │   │   │   ├── Guest.php
│   │   │   │   ├── Folio.php
│   │   │   │   └── FolioTransaction.php
│   │   │   └── Services/
│   │   │       ├── FolioService.php
│   │   │       ├── RateCalculatorService.php
│   │   │       └── AvailabilityService.php
│   │   │
│   │   ├── Restaurant/                 ← Restaurant module
│   │   │   ├── Actions/
│   │   │   ├── Events/
│   │   │   ├── Jobs/
│   │   │   ├── Models/
│   │   │   └── Services/
│   │   │       ├── OrderService.php
│   │   │       ├── KdsService.php
│   │   │       └── MenuService.php
│   │   │
│   │   ├── Bar/                        ← Bar module
│   │   ├── Lounge/                     ← Drink Lounge module
│   │   │
│   │   └── Shared/                     ← Cross-module domain services
│   │       ├── Models/
│   │       │   ├── Outlet.php
│   │       │   ├── Order.php
│   │       │   ├── OrderItem.php
│   │       │   ├── Payment.php
│   │       │   ├── StockItem.php
│   │       │   └── User.php
│   │       ├── Services/
│   │       │   ├── BillingService.php
│   │       │   ├── TaxService.php
│   │       │   ├── PaymentService.php
│   │       │   ├── FiscalizationService.php   ← TRA VFD
│   │       │   └── NotificationService.php
│   │       └── Traits/
│   │           ├── HasFolioCharges.php
│   │           └── BelongsToOutlet.php
│   │
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Api/
│   │   │   │   ├── V1/
│   │   │   │   │   ├── HBMS/
│   │   │   │   │   ├── Restaurant/
│   │   │   │   │   ├── Bar/
│   │   │   │   │   ├── Lounge/
│   │   │   │   │   └── Reporting/
│   │   │   │   └── V2/             ← reserved
│   │   │   └── Auth/
│   │   ├── Middleware/
│   │   │   ├── ResolveTenant.php
│   │   │   ├── EnforcePermission.php
│   │   │   └── AuditRequest.php
│   │   └── Requests/               ← FormRequest validation per action
│   │
│   └── Providers/
│       ├── DomainServiceProvider.php
│       └── EventServiceProvider.php
│
├── database/
│   ├── migrations/
│   │   ├── tenant/                 ← Tenant-scoped migrations
│   │   └── central/                ← Central schema migrations
│   └── seeders/
│       ├── DemoPropertySeeder.php
│       └── TaxConfigSeeder.php
│
├── routes/
│   ├── api.php                     ← Versioned API routes
│   ├── channels.php                ← Broadcasting channel auth
│   └── console.php                 ← Artisan schedule definitions
│
├── config/
│   ├── tenancy.php
│   ├── nexstay.php                 ← App-level config (modules, features)
│   └── integrations.php            ← OTA, gateway, VFD config
│
└── tests/
    ├── Feature/
    │   ├── HBMS/
    │   ├── Restaurant/
    │   ├── Bar/
    │   └── Lounge/
    └── Unit/
```

### 4.2 Action Pattern

All business operations are implemented as **single-responsibility Action classes** (not fat controllers or fat models). This makes testing, auditing, and refactoring straightforward.

```php
<?php
// app/Domain/HBMS/Actions/CheckInGuest.php

namespace App\Domain\HBMS\Actions;

use App\Domain\HBMS\Models\Reservation;
use App\Domain\HBMS\Models\Folio;
use App\Domain\HBMS\Events\GuestCheckedIn;
use App\Domain\Shared\Services\FolioService;
use Illuminate\Support\Facades\DB;

class CheckInGuest
{
    public function __construct(
        private readonly FolioService $folioService
    ) {}

    public function execute(Reservation $reservation, array $options = []): Folio
    {
        return DB::transaction(function () use ($reservation, $options) {
            // 1. Validate state
            throw_if(
                $reservation->status !== 'confirmed',
                \DomainException::class,
                "Reservation {$reservation->booking_ref} is not in confirmed status."
            );

            // 2. Assign room if not pre-assigned
            $room = $options['room_id']
                ? Room::findOrFail($options['room_id'])
                : $this->autoAssignRoom($reservation);

            // 3. Update reservation status
            $reservation->update([
                'status'  => 'checked_in',
                'room_id' => $room->id,
            ]);

            // 4. Update room status
            $room->update(['status' => 'occupied']);

            // 5. Open folio
            $folio = $this->folioService->openFolio($reservation);

            // 6. Fire domain event (triggers: email, dashboard push, key programming job)
            GuestCheckedIn::dispatch($reservation, $room, $folio);

            return $folio;
        });
    }

    private function autoAssignRoom(Reservation $reservation): Room
    {
        return Room::query()
            ->where('room_type_id', $reservation->room_type_id)
            ->where('status', 'vacant_clean')
            ->lockForUpdate()
            ->firstOrFail();
    }
}
```

### 4.3 Service Layer

Services encapsulate reusable logic that spans multiple actions or models.

```php
<?php
// app/Domain/Shared/Services/FolioService.php

namespace App\Domain\Shared\Services;

use App\Domain\HBMS\Models\{Reservation, Folio, FolioTransaction};
use Brick\Money\Money;

class FolioService
{
    public function openFolio(Reservation $reservation): Folio
    {
        return Folio::create([
            'reservation_id' => $reservation->id,
            'folio_number'   => $this->generateFolioNumber(),
            'currency'       => $reservation->rate_plan->currency ?? 'USD',
            'status'         => 'open',
        ]);
    }

    public function postCharge(
        Folio $folio,
        string $type,
        string $description,
        Money  $amount,
        array  $meta = []
    ): FolioTransaction {
        $tax = $this->taxService->calculate($amount, $type);

        return FolioTransaction::create([
            'folio_id'         => $folio->id,
            'transaction_type' => $type,
            'description'      => $description,
            'amount'           => $amount->getAmount()->toFloat(),
            'tax_amount'       => $tax->amount->getAmount()->toFloat(),
            'tax_code'         => $tax->code,
            'reference_id'     => $meta['reference_id'] ?? null,
            'reference_type'   => $meta['reference_type'] ?? null,
            'posted_by'        => auth()->id(),
        ]);
    }

    public function getBalance(Folio $folio): Money
    {
        $total = $folio->transactions()
            ->whereNull('voided_at')
            ->sum('amount');

        return Money::of($total, $folio->currency);
    }

    private function generateFolioNumber(): string
    {
        $prefix = 'FLO-' . date('Y') . '-';
        $last   = Folio::where('folio_number', 'like', $prefix . '%')
                       ->lockForUpdate()
                       ->max('folio_number');
        $seq    = $last ? ((int) substr($last, -6)) + 1 : 1;

        return $prefix . str_pad($seq, 6, '0', STR_PAD_LEFT);
    }
}
```

---

## 5. API Design & Contracts

### 5.1 API Standards

- **Style:** RESTful JSON API following JSON:API 1.1 spec for response envelopes
- **Versioning:** URL path versioning — `/api/v1/...`
- **Authentication:** Bearer token (Sanctum) for mobile/SPA; HTTP-only cookie for Inertia (if chosen)
- **Pagination:** Cursor-based for large lists (orders, transactions); page-based for management tables
- **Filtering:** Query string filter params — `?filter[status]=checked_in&filter[date]=2026-05-15`
- **Sorting:** `?sort=-created_at` (prefix `-` for descending)
- **Rate Limiting:** Per role — Staff: 600 req/min; Admin: 300 req/min; Guest: 60 req/min

### 5.2 Response Envelope

```json
// Success (single resource)
{
  "data": {
    "id": "uuid",
    "type": "reservation",
    "attributes": { ... },
    "relationships": { ... }
  },
  "meta": {
    "request_id": "uuid",
    "timestamp": "2026-05-15T08:00:00Z"
  }
}

// Success (collection)
{
  "data": [ ... ],
  "meta": {
    "total": 142,
    "per_page": 25,
    "current_page": 1,
    "last_page": 6
  },
  "links": {
    "first": "/api/v1/reservations?page=1",
    "last": "/api/v1/reservations?page=6",
    "prev": null,
    "next": "/api/v1/reservations?page=2"
  }
}

// Error
{
  "error": {
    "code": "RESERVATION_CONFLICT",
    "message": "Room 204 is not available for the selected dates.",
    "details": { "room_id": "uuid", "conflict_dates": ["2026-06-10"] },
    "request_id": "uuid"
  }
}
```

### 5.3 Key API Endpoints

#### HBMS

```
# Reservations
GET    /api/v1/reservations                   List reservations (filterable)
POST   /api/v1/reservations                   Create reservation
GET    /api/v1/reservations/{id}              Get reservation detail
PATCH  /api/v1/reservations/{id}              Update reservation
DELETE /api/v1/reservations/{id}              Cancel reservation
POST   /api/v1/reservations/{id}/check-in     Execute check-in
POST   /api/v1/reservations/{id}/check-out    Execute check-out

# Availability
GET    /api/v1/availability                   Room type availability
        ?check_in=2026-06-01&check_out=2026-06-05&adults=2

# Rooms
GET    /api/v1/rooms                          Room status board
PATCH  /api/v1/rooms/{id}/status              Update room status (housekeeping)

# Folios
GET    /api/v1/folios/{id}                    Get folio with all transactions
POST   /api/v1/folios/{id}/transactions       Manual charge posting
DELETE /api/v1/folios/{id}/transactions/{tid} Void a transaction
POST   /api/v1/folios/{id}/settle             Settle folio (payment — any method)
POST   /api/v1/folios/{id}/cash-payment       Record a staff-collected cash payment
POST   /api/v1/folios/{id}/split-payment      Post multiple payment methods against one folio

# Till / Cash Management
POST   /api/v1/tills/open                     Open a till session (start of shift)
GET    /api/v1/tills/active                   Get active session for current outlet/user
POST   /api/v1/tills/{id}/paid-out            Record a cash paid-out (e.g. petty cash)
POST   /api/v1/tills/{id}/bank-drop           Record a cash drop to safe/bank
POST   /api/v1/tills/{id}/close               Submit cash declaration and close session
GET    /api/v1/tills/{id}/summary             Till movement summary for a session
GET    /api/v1/tills/history                  Past sessions (manager view, filterable by date/outlet)
```

#### F&B (shared pattern across restaurant, bar, lounge)

```
# Outlets
GET    /api/v1/outlets                        List outlets
GET    /api/v1/outlets/{id}/tables            Table status board

# Menu
GET    /api/v1/outlets/{id}/menu              Full menu (categories + items)
POST   /api/v1/outlets/{id}/menu/items        Create menu item
PATCH  /api/v1/menu-items/{id}                Update item (price, availability)
POST   /api/v1/menu-items/{id}/toggle         Toggle 86'd status

# Orders
GET    /api/v1/outlets/{id}/orders            Active orders for outlet
POST   /api/v1/outlets/{id}/orders            Open new order
GET    /api/v1/orders/{id}                    Order detail
POST   /api/v1/orders/{id}/items              Add item(s) to order
DELETE /api/v1/orders/{id}/items/{iid}        Void order item
POST   /api/v1/orders/{id}/fire               Send order to KDS
POST   /api/v1/orders/{id}/post-to-folio      Post order total to room folio
POST   /api/v1/orders/{id}/settle             Settle at POS — specify method: cash | card | mobile_money
POST   /api/v1/orders/{id}/cash-payment       Record cash payment with tendered/change amounts
POST   /api/v1/orders/{id}/split-payment      Settle one order with multiple payment methods

# KDS
GET    /api/v1/kds/{outlet_id}                Current KDS queue
POST   /api/v1/kds/items/{id}/bump            Mark item as prepared
```

#### Reporting

```
GET    /api/v1/reports/occupancy              Occupancy report
GET    /api/v1/reports/revenue                Revenue breakdown by module
GET    /api/v1/reports/night-audit/{date}     Night audit report
GET    /api/v1/reports/fb-revenue             F&B revenue by outlet
GET    /api/v1/reports/inventory-variance     Stock variance report
GET    /api/v1/reports/channel-performance    OTA vs. direct breakdown
GET    /api/v1/reports/cash-up/{session_id}   Cash-up report for a till session
GET    /api/v1/reports/cash-over-short        Over/short summary across outlets and date range
GET    /api/v1/reports/payment-methods        Revenue breakdown by payment method (cash vs card vs mobile)
POST   /api/v1/reports/export                 Queue export job (returns job ID)
GET    /api/v1/reports/export/{job_id}        Poll export status / download URL
```

### 5.4 WebSocket Event Contracts

All real-time events are broadcast over Laravel Reverb using private and presence channels.

```
# Channel: private-kds.{outlet_id}
Event: order.item.fired
Payload: { order_id, item_id, item_name, modifiers, notes, table, course, fired_at }

Event: order.item.bumped
Payload: { item_id, bumped_by, bumped_at }

# Channel: private-dashboard.{tenant_id}
Event: room.status.changed
Payload: { room_id, room_number, old_status, new_status, updated_by }

Event: folio.charge.posted
Payload: { folio_id, reservation_id, amount, type, description }

Event: occupancy.updated
Payload: { total_rooms, occupied, occupancy_pct, timestamp }

# Channel: private-table.{outlet_id}
Event: table.status.changed
Payload: { table_id, table_number, old_status, new_status }

# Channel: private-guest.{reservation_id}
Event: folio.updated
Payload: { balance, last_charge_description, last_charge_amount }
```

---

## 6. Real-Time Architecture

### 6.1 Laravel Reverb Setup

Laravel Reverb is the self-hosted WebSocket server. It runs as a separate process alongside the Octane HTTP server.

```bash
# In production: two systemd services
php artisan octane:start --server=swoole --port=8000   # HTTP
php artisan reverb:start --port=8080                    # WebSocket
```

Nginx proxies `/app/*` and `/apps/*` paths to Reverb; all other requests go to Octane.

```nginx
# Nginx upstream config (excerpt)
location /app {
    proxy_pass          http://127.0.0.1:8080;
    proxy_http_version  1.1;
    proxy_set_header    Upgrade $http_upgrade;
    proxy_set_header    Connection "Upgrade";
    proxy_set_header    Host $host;
    proxy_read_timeout  3600s;
}
```

### 6.2 KDS Real-Time Flow

```
Waiter fires order on POS tablet
        │
        ▼
POST /api/v1/orders/{id}/fire
        │
        ▼
OrderService::fireOrder()
  ├── Updates order_items.status → 'sent'
  ├── Stores sent_to_kds_at timestamp
  └── Dispatches: OrderFiredToKds event
                        │
                        ▼
              Broadcasting (Reverb)
                        │
              ┌─────────┴──────────┐
              ▼                    ▼
       KDS Screen             Manager
       (outlet)               Dashboard
   Shows new item         Shows order count
   with countdown timer   update in real time
```

### 6.3 Queue Configuration (Horizon)

```php
// config/horizon.php — queue worker pools
'environments' => [
    'production' => [
        'supervisor-default' => [
            'connection' => 'redis',
            'queue'      => ['high', 'default', 'low'],
            'balance'    => 'auto',
            'processes'  => 10,
            'tries'      => 3,
        ],
        'supervisor-reports' => [
            'queue'      => ['reports'],
            'processes'  => 3,
            'timeout'    => 120,   // Reports can take up to 2 minutes
        ],
    ],
],
```

Queue priority assignment:

| Queue | Jobs | Priority |
|---|---|---|
| `high` | KDS push, folio charge posting, payment processing | Immediate |
| `default` | Email/SMS notifications, OTA sync, VFD fiscalization | Normal |
| `low` | Report generation, inventory alerts, backup | Background |
| `reports` | Scheduled and on-demand report exports | Dedicated workers |

---

## 7. Authentication & Authorization

### 7.1 Authentication Flow

**Staff (web dashboard / POS tablets):** Laravel Sanctum SPA authentication via HTTP-only cookie.

**Mobile API (guest app):** Sanctum personal access tokens with 30-day expiry + refresh.

**Server-to-server (OTA webhooks):** Shared secret HMAC-SHA256 signature verification.

```php
// routes/api.php
Route::prefix('v1')->group(function () {

    // Public routes (no auth)
    Route::post('/auth/login', [AuthController::class, 'login']);
    Route::get('/availability', [AvailabilityController::class, 'index']);
    Route::post('/bookings', [PublicBookingController::class, 'store']);

    // Authenticated routes
    Route::middleware('auth:sanctum')->group(function () {

        // Guest-accessible routes
        Route::middleware('role:guest')->group(function () {
            Route::get('/my/folio', ...);
            Route::get('/my/reservation', ...);
            Route::post('/my/orders', ...);         // QR code ordering
        });

        // Staff routes
        Route::middleware('role:staff|manager|admin')->group(function () {
            Route::apiResource('reservations', ReservationController::class);
            Route::apiResource('orders', OrderController::class);
            // ...
        });

        // Manager+ routes
        Route::middleware('permission:manage-reports')->group(function () {
            Route::get('/reports/*', ...);
        });

        // Admin-only
        Route::middleware('role:admin')->group(function () {
            Route::apiResource('users', UserController::class);
            Route::apiResource('tax-configs', TaxConfigController::class);
        });
    });
});
```

### 7.2 RBAC with spatie/laravel-permission

```php
// Roles defined at tenant level (not global)
$roles = [
    'super_admin',
    'general_manager',
    'front_desk',
    'housekeeper',
    'fb_manager',
    'waiter',
    'bartender',
    'lounge_staff',
    'head_chef',
    'finance',
    'it_admin',
    'guest',
];

// Example permission assignments
$waiter_permissions = [
    'create-orders',
    'update-orders',
    'view-menu',
    'fire-order-to-kds',
    'post-order-to-folio',      // charge to room
    'record-cash-payment',      // accept and record cash at table
];

$fb_manager_permissions = [
    ...$waiter_permissions,
    'manage-menu',
    'void-orders',
    'view-fb-reports',
    'manage-inventory',
    'manage-staff-shifts',
    'open-till-session',        // open/close cash drawer for outlet
    'close-till-session',
    'approve-till-paid-out',    // authorise petty cash disbursements
    'approve-bank-drop',        // authorise cash drops to safe
    'view-cash-up-reports',
];

$general_manager_permissions = [
    ...$fb_manager_permissions,
    'view-all-reports',
    'approve-discounts',
    'approve-night-audit',
    'manage-rate-plans',
    'reconcile-till-sessions',  // mark flagged sessions as reconciled after investigation
    'view-over-short-report',   // cross-outlet cash variance visibility
];
```

### 7.3 MFA Implementation

MFA is enforced for roles: `super_admin`, `general_manager`, `finance`, `it_admin`.

- **Method:** TOTP (Google Authenticator compatible) via `pragmarx/google2fa-laravel`
- **Recovery codes:** 8 single-use codes generated on MFA setup
- **Enforcement:** Middleware `RequiresMfa` on all sensitive routes — if MFA enabled on role but not configured by user, redirect to setup screen

---

## 8. Multi-Tenancy Architecture

### 8.1 Tenant Identification

Each hotel property is a tenant. Tenant resolution uses the **subdomain** pattern:

```
zanzibar-pearl.nexstay.io     → tenant: zanzibar_pearl
serengeti-lodge.nexstay.io    → tenant: serengeti_lodge
```

For on-premise or white-label deployments, tenant resolution falls back to a custom domain mapping stored in the central database.

### 8.2 Tenant Provisioning Flow

```
Admin creates new property in NexStay HQ portal
        │
        ▼
POST /central/api/tenants
        │
        ▼
TenantProvisioningJob (queued)
  ├── Creates tenant record in central DB
  ├── Creates PostgreSQL schema: tenant_{slug}
  ├── Runs tenant migrations on new schema
  ├── Seeds: tax configs, default outlet, admin user
  ├── Configures DNS subdomain (Cloudflare API)
  └── Sends welcome email with setup link
                        │
                        ▼
              Property is live in < 5 minutes
```

### 8.3 Central vs. Tenant Data

| Data | Location | Rationale |
|---|---|---|
| Tenant registry, billing, HQ config | Central schema (`public`) | Cross-property visibility |
| All property data (rooms, reservations, orders, folios, staff) | Tenant schema | Full isolation |
| Global rate plans / package templates | Central schema | Shared starting templates |
| System users (NexStay support staff) | Central schema | Cross-tenant access |
| Property users (hotel staff, guests) | Tenant schema | Data isolation |

---

## 9. Module Technical Specifications

### 9.1 HBMS — Availability Engine

Availability calculation must be **fast** (< 200ms) and **accurate** (prevent double-booking). Strategy:

```
1. Nightly: Pre-compute availability grid into Redis
   Key: avail:{room_type_id}:{date}  →  count of available rooms

2. On booking: Decrement Redis counter + create reservation in DB transaction
   (optimistic locking — retry on conflict)

3. On cancellation: Increment Redis counter + update reservation

4. On OTA sync: Redis counter updated after OTA reservation lands
```

```php
// AvailabilityService.php (excerpt)
public function getAvailable(RoomType $type, Carbon $from, Carbon $to): Collection
{
    $dates = CarbonPeriod::create($from, $to->subDay());

    return collect($dates)->mapWithKeys(function ($date) use ($type) {
        $key   = "avail:{$type->id}:{$date->toDateString()}";
        $count = Cache::get($key) ?? $this->computeFromDb($type, $date);

        return [$date->toDateString() => (int) $count];
    });
}
```

### 9.2 Night Audit — Technical Flow

The night audit is a **scheduled Artisan command** running at 23:59 local time per tenant.

```php
// Console/Commands/RunNightAudit.php
public function handle(): void
{
    $tenants = Tenant::active()->get();

    foreach ($tenants as $tenant) {
        $tenant->run(function () {
            NightAuditJob::dispatch()->onQueue('high');
        });
    }
}
```

Night audit steps (all within a DB transaction):

```
1.  Post room charges for all checked-in reservations (rate × nights)
2.  Apply inclusive/exclusive tax per room charge
3.  Process no-shows: mark status, apply penalty charge if policy set
4.  Validate rate integrity (actual charge = rate plan × date)
5.  Age city ledger balances (flag overdue accounts)
6.  ── CASH RECONCILIATION ──
    a. Flag any till sessions that are still 'open' at audit time (should be closed)
    b. Alert GM and outlet manager of unclosed tills
    c. Auto-compute system_cash for all sessions closed today
    d. Summarise total cash collected across all outlets vs. total cash payments in system
    e. Include over/short totals per outlet in the audit report
7.  Generate revenue journal entries (rooms + F&B + all payment methods itemised)
8.  Compute occupancy stats and cache for dashboard
9.  Generate PDF audit report (includes cash-up summary per outlet)
10. Email report to GM and Finance
11. Roll audit date to next day
12. Release audit lock (allow new-day transactions)
```

### 9.3 KDS — Order Routing

Orders can span multiple KDS stations (grill, cold kitchen, bar).

```
Order submitted
        │
        ▼
KdsService::route(Order $order)
        │
        ├── For each OrderItem:
        │       - Lookup item's KDS station from menu_items.settings->kds_station
        │       - Group items by station
        │
        └── For each station group:
                - Broadcast to: private-kds.{outlet_id}.{station}
                - Store KDS routing record (for bump tracking)
```

### 9.4 Inventory Deduction

Stock is deducted **asynchronously** on order close (not on order fire) to avoid POS latency.

```php
// Triggered by: OrderClosed event listener
class DeductInventoryOnOrderClose
{
    public function handle(OrderClosed $event): void
    {
        foreach ($event->order->items as $item) {
            foreach ($item->menuItem->recipeIngredients as $ingredient) {
                $qty = $ingredient->quantity * $item->quantity;

                StockMovement::create([
                    'stock_item_id'  => $ingredient->stock_item_id,
                    'movement_type'  => 'consumption',
                    'quantity'       => -$qty,
                    'reference_id'   => $event->order->id,
                    'reference_type' => Order::class,
                ]);

                // Decrement current stock (with floor at 0 for safety)
                $ingredient->stockItem->decrement('current_stock', $qty);

                // Trigger low-stock alert if below reorder level
                if ($ingredient->stockItem->current_stock <= $ingredient->stockItem->reorder_level) {
                    LowStockAlert::dispatch($ingredient->stockItem)->onQueue('low');
                }
            }
        }
    }
}
```

---

## 10. Integrations

### 10.1 OTA Channel Manager

NexStay implements the **OTA XML API** (Booking.com, Expedia) and JSON REST (Airbnb) for 2-way sync.

```
NexStay → OTA (Push)              OTA → NexStay (Webhook/Pull)
─────────────────────             ──────────────────────────────
Rate updates                      New reservation webhook
Availability updates              Cancellation webhook
Restriction updates               Modification webhook
                                  Review posted (future)
```

```php
// SyncAvailabilityToOta.php (queued job)
class SyncAvailabilityToOta implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue;

    public $tries = 5;
    public $backoff = [10, 30, 60, 120, 300]; // exponential backoff seconds

    public function handle(OtaGatewayService $gateway): void
    {
        $payload = $this->buildAvailabilityPayload();

        foreach (OtaConnection::active()->get() as $connection) {
            $gateway->pushAvailability($connection, $payload);
        }
    }
}
```

### 10.2 Cash Payment Handling

Cash is a **first-class payment method** in NexStay — not an afterthought. In East African hospitality, a significant share of F&B, walk-in, and even room settlements are completed in cash. The system must record, reconcile, and audit every cash transaction with the same rigour as electronic payments.

#### 10.2.1 Till Session Lifecycle

A till session must be **open** before any cash transaction can be recorded. This is enforced at the middleware layer — attempting to post a cash payment without an active session returns a `422 TILL_NOT_OPEN` error.

```
Manager/Supervisor opens shift
        │
        ▼
POST /api/v1/tills/open
  Body: { outlet_id, float_amount, currency, notes }
        │
        ▼
TillSessionService::open()
  ├── Creates till_sessions record (status: open)
  ├── Records opening_float in till_movements (+float_amount)
  └── Broadcasts till.opened to manager dashboard
        │
        ▼
Staff POS is now unlocked for cash transactions in that outlet
```

#### 10.2.2 Recording a Staff Cash Payment

```
Guest hands over cash to waiter / front desk
        │
        ▼
Staff enters on POS / reception dashboard:
  - Amount tendered (e.g. 50,000 TZS)
  - Order or Folio reference

POST /api/v1/orders/{id}/cash-payment
  Body: {
    "till_session_id": "uuid",
    "amount_tendered": 50000,
    "currency": "TZS"
  }
        │
        ▼
CashPaymentService::record()
  ├── Validates active till session belongs to this outlet
  ├── Calculates change: tendered - order_total
  ├── Creates payment record:
  │     method:        cash
  │     amount:        order_total
  │     cash_tendered: 50000
  │     cash_change:   (50000 - order_total)
  │     received_by:   auth()->id()
  │     status:        captured   ← cash is immediate; no async capture
  ├── Creates till_movement:
  │     type:    cash_payment
  │     amount:  +order_total   ← change is NOT added to drawer (guest takes it)
  │     reference_id: payment_id
  ├── Posts charge to folio (if room charge requested simultaneously)
  ├── Triggers fiscalization job (TRA VFD) → onQueue('default')
  └── Returns: { payment_id, change_due, receipt_number }
        │
        ▼
POS screen displays: "Change due: TZS 12,500"
Staff gives change, transaction complete
```

#### 10.2.3 Cash Payment Validation Rules

```php
// CashPaymentRequest.php
public function rules(): array
{
    return [
        'till_session_id' => ['required', 'uuid', 'exists:till_sessions,id',
            // Custom rule: session must be open and belong to same outlet as order
            new ActiveTillSessionForOutlet($this->route('order'))],
        'amount_tendered' => ['required', 'numeric',
            // Tendered must be >= the amount due
            'min:' . $this->route('order')->balance_due],
        'currency'        => ['required', 'string', 'size:3'],
        'notes'           => ['nullable', 'string', 'max:500'],
    ];
}
```

#### 10.2.4 Paid-Out (Petty Cash)

Petty cash disbursements from the till (e.g. paying a delivery rider, buying ice) must be recorded as **paid-outs**, requiring manager PIN authorization.

```
POST /api/v1/tills/{id}/paid-out
  Body: {
    "amount": 5000,
    "currency": "TZS",
    "description": "Ice bags from market",
    "manager_pin": "****"
  }
        │
        ▼
TillSessionService::paidOut()
  ├── Verifies manager PIN (checks user has permission: approve-till-paid-out)
  ├── Creates till_movement: { type: paid_out, amount: -5000 }
  └── Logs to activity log with manager ID
```

#### 10.2.5 Bank Drop / Safe Drop

When the drawer accumulates too much cash, a supervisor can record a bank drop — physically moving cash to the safe or bank without closing the session.

```
POST /api/v1/tills/{id}/bank-drop
  Body: { "amount": 200000, "currency": "TZS", "notes": "Dropped to safe" }
        │
        ▼
till_movement: { type: bank_drop, amount: -200000 }
  ← Reduces system_cash expectation by 200,000 TZS
  ← Requires supervisor approval (same flow as paid-out)
```

#### 10.2.6 Shift Cash-Up & Reconciliation

```
End of shift: staff/manager initiates cash-up

POST /api/v1/tills/{id}/close
  Body: { "declared_cash": 487500 }
        │
        ▼
TillSessionService::close()
  ├── Computes system_cash:
  │     = opening_float
  │     + SUM(cash_payments)
  │     - SUM(cash_refunds)
  │     - SUM(paid_outs)
  │     - SUM(bank_drops)
  │
  ├── Sets over_short = declared_cash - system_cash
  │     Positive = OVER  (staff has more cash than expected)
  │     Negative = SHORT (staff has less cash than expected)
  │
  ├── Closes till_session (status: closed)
  ├── Generates cash-up PDF report
  └── Broadcasts till.closed to manager dashboard
        │
        ▼
If |over_short| > config('nexstay.till.variance_threshold'):
  ├── Flags session for manager review
  ├── Sends alert to F&B Manager / GM
  └── Session status remains 'closed' until manager marks it 'reconciled'
```

#### 10.2.7 Foreign Currency Cash

Staff can accept foreign currency and record the exchange rate applied. The system posts the **converted base-currency amount** to the folio while the `foreign_currency_transactions` table preserves the raw foreign amount for audit.

```php
// ForeignCurrencyPaymentService.php
public function record(Order $order, array $data): Payment
{
    $foreignAmount  = Money::of($data['foreign_amount'], $data['foreign_currency']);
    $exchangeRate   = $data['exchange_rate'];  // entered by staff, or fetched from rate API
    $baseAmount     = $foreignAmount->multipliedBy($exchangeRate, RoundingMode::HALF_UP);

    $payment = $this->cashPaymentService->record($order, [
        'amount'        => $baseAmount->getAmount()->toFloat(),
        'currency'      => $data['base_currency'],
        'cash_tendered' => $data['foreign_amount'],  // shown on receipt in foreign currency
        'notes'         => "Paid in {$data['foreign_currency']} @ {$exchangeRate}",
    ]);

    ForeignCurrencyTransaction::create([
        'payment_id'       => $payment->id,
        'foreign_currency' => $data['foreign_currency'],
        'foreign_amount'   => $data['foreign_amount'],
        'exchange_rate'    => $exchangeRate,
        'base_currency'    => $data['base_currency'],
        'base_amount'      => $baseAmount->getAmount()->toFloat(),
        'rate_source'      => $data['rate_source'] ?? 'manual',
        'recorded_by'      => auth()->id(),
    ]);

    return $payment;
}
```

### 10.3 Payment Gateways (Online / Electronic)

A unified `PaymentService` abstracts all gateway implementations behind a common interface.

```php
interface PaymentGatewayInterface
{
    public function charge(Money $amount, array $meta): PaymentResult;
    public function refund(string $gatewayRef, Money $amount): RefundResult;
    public function createPaymentIntent(Money $amount): PaymentIntent;
}

// Implementations: StripeGateway | FlutterwaveGateway | AzamPayGateway
// Selected at runtime based on tenant config: config('nexstay.payment_gateway')
```

**Mobile Money (Azam Pay) — Tanzania specific:**

```
Customer requests payment
        │
        ▼
POST /payments (method: mobile_money)
        │
        ▼
AzamPayGateway::createPaymentIntent()
  → Returns: payment_ref + USSD push to customer's phone
        │
Customer enters PIN on phone
        │
        ▼
Azam Pay sends webhook to: /api/v1/webhooks/azampay
  → Verified via HMAC-SHA256 signature
  → Updates payment status → 'captured'
  → Posts charge to folio
  → Broadcasts folio.updated to guest's channel
```

### 10.4 TRA VFD Fiscalization (Tanzania)

```php
// FiscalizationService.php
class FiscalizationService
{
    public function fiscalize(Payment $payment): void
    {
        $payload = $this->buildVfdPayload($payment);

        $response = Http::timeout(10)
            ->retry(3, 500)
            ->post(config('integrations.tra_vfd.endpoint'), $payload);

        if ($response->successful()) {
            $payment->update([
                'fiscalized_at' => now(),
                'fiscal_ref'    => $response->json('receiptNumber'),
            ]);
        } else {
            // Log failure, queue retry — do NOT block payment completion
            Log::error('VFD fiscalization failed', [
                'payment_id' => $payment->id,
                'response'   => $response->body(),
            ]);
            FiscalizePaymentRetry::dispatch($payment)->delay(60)->onQueue('default');
        }
    }
}
```

---

## 11. Frontend Architecture Decision

### 11.1 Options Analysis

| Option | Pros | Cons | Best For |
|---|---|---|---|
| **React + Inertia.js** | Single codebase, no separate API layer for server-rendered views, fast dev, built-in SSR | Less flexible for native mobile later, tighter Laravel coupling | Teams that want monorepo simplicity |
| **React SPA + Laravel API** | True decoupling, same API serves web and mobile, easier to scale frontend independently | More initial setup (CORS, auth tokens, two deploys) | Projects with a mobile app roadmap |
| **Next.js + Laravel API** | SSR for SEO, fast initial load, Vercel deployment simplicity | Overkill for staff-only dashboards, added infra complexity | Public-facing marketing + booking engine only |

### 11.2 Recommendation — Hybrid Approach

**Decision: React SPA (Vite) + Laravel API, with Inertia.js for admin-only views**

| Interface | Frontend | Rationale |
|---|---|---|
| Guest Booking Engine | Next.js (React) | Needs SEO, fast public page load, and SSR for availability pages |
| Staff Dashboard (Reception, F&B, Admin) | Inertia.js + React | No SEO needed; Inertia eliminates API boilerplate for admin |
| POS Tablets (Restaurant, Bar, Lounge) | React Native | Native feel, offline capability, hardware integration |
| Guest Mobile App | React Native | iOS + Android from single codebase |
| KDS Screen | React SPA | Runs in Chrome kiosk mode on dedicated tablet |

This hybrid approach avoids the "one frontend to rule them all" trap. Each interface is right-sized for its use case while all sharing the same Laravel API and WebSocket backend.

### 11.3 Frontend Stack

```
Booking Engine (Next.js)
  ├── Next.js 15 (App Router)
  ├── TypeScript
  ├── Tailwind CSS
  ├── React Query (server state)
  ├── Zustand (client state)
  └── Stripe.js / Flutterwave.js

Staff Dashboard + Admin (Inertia + React)
  ├── Laravel Inertia.js
  ├── React 19
  ├── TypeScript
  ├── Tailwind CSS
  ├── Shadcn/ui component library
  ├── Recharts (dashboards)
  └── TanStack Table (data grids)

POS + Guest App (React Native)
  ├── React Native 0.74+
  ├── Expo (managed workflow)
  ├── TypeScript
  ├── React Navigation
  ├── Zustand
  └── React Native MMKV (offline storage)
```

---

## 12. Infrastructure & DevOps

### 12.1 Deployment Architecture

```
┌────────────────────────────────────────────────────────────┐
│                    Cloud Provider (AWS / DigitalOcean)      │
│                                                            │
│  ┌──────────────┐    ┌──────────────┐    ┌─────────────┐  │
│  │  Load        │    │  App Servers │    │  DB Cluster │  │
│  │  Balancer    │───►│  (2+ nodes)  │───►│  PostgreSQL │  │
│  │  (Nginx/ALB) │    │  Octane +    │    │  Primary +  │  │
│  └──────────────┘    │  Reverb      │    │  Replica    │  │
│                      └──────┬───────┘    └─────────────┘  │
│                             │                              │
│                      ┌──────▼───────┐    ┌─────────────┐  │
│                      │  Redis       │    │  Meilisearch│  │
│                      │  (Cache +    │    │  (Search)   │  │
│                      │   Queue)     │    └─────────────┘  │
│                      └──────────────┘                     │
│                                           ┌─────────────┐  │
│                                           │  S3 / MinIO │  │
│                                           │  (Storage)  │  │
│                                           └─────────────┘  │
└────────────────────────────────────────────────────────────┘
```

### 12.2 Environment Configuration

Three environments:

| Environment | Purpose | Database | Octane | Reverb |
|---|---|---|---|---|
| `local` | Developer workstation (Laravel Sail / Docker) | Local PostgreSQL | ❌ (php artisan serve) | ❌ (Soketi mock) |
| `staging` | QA / UAT; mirrors production config | Shared staging DB | ✅ | ✅ |
| `production` | Live tenants | Managed PostgreSQL cluster | ✅ | ✅ |

### 12.3 Docker Compose (Development)

```yaml
# docker-compose.yml
services:
  app:
    build: .
    ports: ["8000:8000", "8080:8080"]
    volumes: [".:/var/www/html"]
    depends_on: [postgres, redis, meilisearch]

  postgres:
    image: postgres:16-alpine
    environment:
      POSTGRES_DB: nexstay
      POSTGRES_USER: nexstay
      POSTGRES_PASSWORD: secret
    volumes: ["pgdata:/var/lib/postgresql/data"]
    ports: ["5432:5432"]

  redis:
    image: redis:7-alpine
    ports: ["6379:6379"]

  meilisearch:
    image: getmeili/meilisearch:v1.7
    ports: ["7700:7700"]

  horizon:
    build: .
    command: php artisan horizon
    depends_on: [app, redis]

  reverb:
    build: .
    command: php artisan reverb:start --host=0.0.0.0 --port=8080
    ports: ["8080:8080"]

volumes:
  pgdata:
```

### 12.4 CI/CD Pipeline

```
Developer pushes to feature branch
        │
        ▼
GitHub Actions Pipeline
  ├── [Lint]       PHP CS Fixer + ESLint
  ├── [Test]       PHPUnit (Feature + Unit) on PostgreSQL test instance
  ├── [Security]   composer audit + npm audit
  └── [Build]      Docker image build + push to registry
        │
        ▼ (merge to main)
  ├── [Staging Deploy]  Zero-downtime deploy via Laravel Envoyer / custom script
  │   └── php artisan migrate --force (tenant migrations run automatically)
  │
        ▼ (manual approval for production)
  └── [Production Deploy]
      ├── Maintenance mode OFF (rolling deploy — no downtime)
      ├── php artisan octane:reload
      └── Notify team via Slack
```

---

## 13. Testing Strategy

### 13.1 Test Pyramid

```
         /\
        /  \        E2E Tests (~50 scenarios)
       /────\       Playwright — critical guest journeys
      /      \
     /────────\     Feature Tests (~600 tests)
    /          \    PHPUnit — API endpoints, full request/response
   /────────────\
  /              \  Unit Tests (~800 tests)
 /────────────────\ PHPUnit — Services, Actions, calculations
```

### 13.2 Feature Test Example

```php
// tests/Feature/HBMS/CheckInTest.php
class CheckInTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    /** @test */
    public function front_desk_agent_can_check_in_a_confirmed_reservation(): void
    {
        $agent       = User::factory()->withRole('front_desk')->create();
        $room        = Room::factory()->vacantClean()->create();
        $reservation = Reservation::factory()
                           ->confirmed()
                           ->forRoomType($room->roomType)
                           ->create();

        $response = $this->actingAs($agent)
            ->postJson("/api/v1/reservations/{$reservation->id}/check-in", [
                'room_id' => $room->id,
            ]);

        $response->assertOk()
                 ->assertJsonPath('data.attributes.folio_status', 'open');

        $this->assertDatabaseHas('reservations', [
            'id'     => $reservation->id,
            'status' => 'checked_in',
            'room_id' => $room->id,
        ]);

        $this->assertDatabaseHas('rooms', [
            'id'     => $room->id,
            'status' => 'occupied',
        ]);

        Event::assertDispatched(GuestCheckedIn::class);
    }

    /** @test */
    public function check_in_fails_if_reservation_is_not_confirmed(): void
    {
        $agent       = User::factory()->withRole('front_desk')->create();
        $reservation = Reservation::factory()->cancelled()->create();

        $this->actingAs($agent)
             ->postJson("/api/v1/reservations/{$reservation->id}/check-in")
             ->assertUnprocessable()
             ->assertJsonPath('error.code', 'RESERVATION_CONFLICT');
    }
}
```

### 13.3 Test Coverage Targets

| Layer | Target | Tool |
|---|---|---|
| Unit (Services, Actions) | ≥ 90% | PHPUnit |
| Feature (API endpoints) | ≥ 80% | PHPUnit |
| Frontend components | ≥ 70% | Vitest + React Testing Library |
| E2E critical paths | 100% of defined scenarios | Playwright |
| Performance | P95 < 500ms on all endpoints | k6 load tests |

### 13.4 Critical E2E Scenarios (must pass before every production deploy)

1. Guest completes room booking via booking engine
2. Front desk checks in a guest and opens a folio
3. Waiter creates order, fires to KDS, chef bumps, order auto-posts to folio
4. **Staff opens a till session, records a cash payment with change due, and the till movement is reflected in the session summary**
5. Bartender opens tab, adds drinks, closes tab with mobile money payment
6. **Manager performs end-of-shift cash-up: declared amount matches system amount; report generated**
7. **Guest pays restaurant bill by splitting: partial cash + partial card; both legs post to folio correctly**
8. Night audit closes successfully and generates PDF
9. OTA reservation arrives via webhook and appears in booking calendar
10. Guest checks out with itemized invoice including F&B charges and all payment methods listed

---

## 14. Security Implementation

### 14.1 Input Validation

All API inputs pass through **Laravel FormRequest** classes. No raw `$request->all()` in controllers.

```php
class CreateReservationRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'guest_id'       => ['required', 'uuid', 'exists:guests,id'],
            'room_type_id'   => ['required', 'uuid', 'exists:room_types,id'],
            'check_in_date'  => ['required', 'date', 'after_or_equal:today'],
            'check_out_date' => ['required', 'date', 'after:check_in_date'],
            'adults'         => ['required', 'integer', 'min:1', 'max:10'],
            'children'       => ['integer', 'min:0', 'max:6'],
            'rate_plan_id'   => ['nullable', 'uuid', 'exists:rate_plans,id'],
        ];
    }
}
```

### 14.2 SQL Injection Prevention

- **Never** use raw string interpolation in queries
- Always use Eloquent or parameterized Query Builder
- Raw queries (`DB::select`) must use bindings: `DB::select('SELECT * FROM rooms WHERE id = ?', [$id])`

### 14.3 Sensitive Data Handling

```php
// Guest model — encrypt PII at rest
class Guest extends Model
{
    use HasEncryptedAttributes;  // spatie/laravel-model-encryption or custom

    protected array $encrypted = [
        'id_number',
        'date_of_birth',
        'phone',
    ];

    // Email stored hashed for lookup + encrypted for display
    protected $casts = [
        'email' => EncryptedCast::class,
    ];
}
```

### 14.4 Webhook Security

All incoming webhooks (OTA, payment gateways) are verified via HMAC-SHA256 signature.

```php
class VerifyWebhookSignature
{
    public function handle(Request $request, Closure $next, string $source): Response
    {
        $signature = $request->header('X-Signature');
        $secret    = config("integrations.{$source}.webhook_secret");
        $expected  = hash_hmac('sha256', $request->getContent(), $secret);

        if (! hash_equals($expected, $signature ?? '')) {
            abort(401, 'Invalid webhook signature.');
        }

        return $next($request);
    }
}
```

---

## 15. Performance & Scalability

### 15.1 Query Optimization Rules

- All foreign keys must have database indexes
- Use `select()` to fetch only needed columns — never `SELECT *` in production code
- Eager load relationships to eliminate N+1: `Reservation::with(['guest', 'room', 'folio.transactions'])`
- Use cursor pagination for large order/transaction lists
- Raw reporting queries use `DB::table()` with explicit column selects, not Eloquent
- Slow query log threshold: 100ms in production; review weekly

### 15.2 Caching Strategy

```
Layer 1: Application cache (Redis, TTL-based)
  - Room availability grids → 5 minutes (busted on reservation change)
  - Menu items per outlet → 15 minutes (busted on menu update)
  - Tax config → 60 minutes (busted on config change)
  - Dashboard occupancy stats → 2 minutes

Layer 2: HTTP response cache (Nginx)
  - Public booking engine availability → 60 seconds (Vary: Accept-Language)
  - Menu QR code pages → 5 minutes (CDN cacheable)

Layer 3: DB query cache
  - PostgreSQL shared_buffers configured at 25% of available RAM
  - Frequently-accessed tables (room_types, rate_plans) stay in pg buffer pool
```

### 15.3 Horizontal Scaling Path

```
Current (single property, < 100 concurrent):
  1 Octane server + 1 Reverb server + 1 PostgreSQL + 1 Redis

Scale-out path (multi-property, 500+ concurrent):
  ├── Add Octane nodes behind load balancer (sticky sessions via Redis)
  ├── Reverb horizontally scaled (Redis pub/sub backend)
  ├── PostgreSQL → managed cluster (AWS RDS Multi-AZ or Supabase)
  ├── Redis → Redis Cluster or ElastiCache
  └── Horizon workers scaled per queue via Kubernetes HPA
```

---

## 16. Development Standards

### 16.1 Code Style

- **PHP:** PSR-12 enforced via PHP CS Fixer in CI
- **TypeScript/React:** ESLint + Prettier with project `.eslintrc`
- **Git:** Conventional Commits spec (`feat:`, `fix:`, `chore:`, `docs:`, `test:`)
- **Branch strategy:** GitHub Flow — feature branches from `main`, squash-merge PRs

### 16.2 Commit & PR Rules

- No direct pushes to `main` or `staging`
- Every PR requires: passing CI, 1 peer review, and no unresolved comments
- PR description must reference the related ticket/issue
- Migrations must be **reversible** — every `up()` must have a working `down()`

### 16.3 Artisan Commands Reference

```bash
# Development
php artisan serve                         # Local HTTP server
php artisan reverb:start                  # Local WebSocket server
php artisan horizon                       # Queue worker with dashboard

# Tenancy
php artisan tenants:create {name} {slug}  # Provision new tenant
php artisan tenants:migrate               # Run migrations for all tenants
php artisan tenants:seed {tenant}         # Seed a specific tenant

# Domain operations
php artisan nexstay:night-audit           # Manually trigger night audit
php artisan nexstay:sync-ota              # Manually trigger OTA sync
php artisan nexstay:rebuild-availability  # Rebuild Redis availability cache

# Maintenance
php artisan telescope:prune               # Prune Telescope records (run daily)
php artisan horizon:snapshot              # Horizon metrics snapshot
php artisan backup:run                    # Manual backup trigger
```

### 16.4 Logging Standards

- All logs use **structured JSON format** in production (`LOG_CHANNEL=json`)
- Log levels: `DEBUG` (local only), `INFO` (normal operations), `WARNING` (recoverable), `ERROR` (requires investigation), `CRITICAL` (page on-call)
- Never log raw passwords, full card numbers, or unmasked ID numbers
- All financial operations log: actor, action, entity, before/after amounts

---

## 17. Cash Handling & Till Management

### 17.1 Overview & Design Philosophy

Cash is treated as a **fully trackable asset** in NexStay, not a free-text note field. Every cash event — from the opening float to the last paid-out — is a database record with a staff member, a timestamp, and an audit trail. The goal is to give managers the same confidence in cash accuracy as they have in card and mobile money settlements.

Key principles:
- **No cash payment without an open till session.** The system enforces this at middleware level.
- **Every drawer movement is a ledger entry.** Cash in and cash out are explicit `till_movements`, not inferred from payment totals.
- **System cash is always computed, never entered.** Staff declare what they count; the system tells them what it expected. The gap is the over/short.
- **Accountability is per-staff, per-shift.** Each till session is owned by the staff member who opened it, and `received_by` on every cash payment links back to the individual.
- **Foreign currency is recorded in full.** The foreign amount, the exchange rate, and the converted base amount are all stored — the folio carries base currency only.

### 17.2 Till Session States

```
            open()
  [none] ──────────────► [open]
                            │
                     close()│
                            ▼
                        [closed]
                            │
       manager review &     │ reconcile()
       over/short > threshold│
                            ▼
                       [reconciled]
```

A session cannot be re-opened once closed. If a mistake is made after closing, a manager can open a new session and post a corrective `till_movement` with `type: adjustment`.

### 17.3 Split Payments (Cash + Other Methods)

A single order or folio can be settled using **multiple payment methods**. This is common when a guest pays part cash and tops up with card, or when a room bill is split between cash and mobile money.

```
POST /api/v1/orders/{id}/split-payment
Body:
{
  "payments": [
    { "method": "cash", "amount": 30000, "currency": "TZS",
      "till_session_id": "uuid", "cash_tendered": 50000 },
    { "method": "card", "amount": 15000, "currency": "TZS",
      "gateway": "stripe" }
  ]
}
```

The `SplitPaymentService` validates that the sum of all payment legs equals the outstanding balance before committing any leg. All legs are inserted in a single DB transaction — either all succeed or all roll back.

### 17.4 Cash-Up Report Contents

The cash-up PDF generated at `POST /api/v1/tills/{id}/close` contains:

| Section | Content |
|---|---|
| Header | Outlet name, date, shift, session ID, opened by |
| Opening Float | Float amount recorded at session open |
| Cash Receipts | Itemised list of every cash payment (time, order/folio ref, amount, received by) |
| Cash Refunds | Any cash refunds issued during the shift |
| Paid-Outs | Petty cash disbursements with reason and approver |
| Bank Drops | Any safe/bank drops during shift |
| System Cash | Computed expected closing balance |
| Declared Cash | Amount staff entered at close |
| **Over / Short** | **Variance highlighted — green if zero, amber if within threshold, red if over threshold** |
| Signature Line | Space for manager signature on printed copy |

### 17.5 Variance Thresholds & Alerts

```php
// config/nexstay.php
'till' => [
    'variance_threshold' => env('TILL_VARIANCE_THRESHOLD', 2000), // TZS 2,000 default
    'alert_roles'        => ['general_manager', 'fb_manager'],
    'auto_reconcile_zero_variance' => true,  // sessions with 0 over/short auto-reconcile
],
```

When a session closes with `|over_short| > variance_threshold`:
- Session status stays `closed` (not `reconciled`)
- Alert pushed to GM and F&B Manager via dashboard notification + SMS
- Session is highlighted red on the Till History manager view
- Night audit report flags the outlet's unreconciled sessions

### 17.6 Audit Trail & Fraud Prevention

- Every `till_movement` records `performed_by` (the staff member) and `approved_by` (the manager, for paid-outs/drops)
- Void of a cash payment requires a manager PIN and creates an inverse `till_movement` — it does **not** delete the original records
- The activity log (`spatie/laravel-activitylog`) captures before/after state on all `payments` and `till_sessions` modifications
- Night audit sends a cross-outlet **payment method breakdown** to Finance: total cash, total card, total mobile money — allowing Finance to reconcile against physical cash received without relying on individual outlet reports

### 17.7 POS UI Requirements for Cash (Frontend)

The following UI behaviours must be implemented on the POS tablet for cash flows:

- **Change calculator:** When staff enters the tendered amount, the POS immediately displays the change due in large text before confirming the payment. Confirmation is a separate tap.
- **Till not open guard:** If no active till session exists for the outlet, the "Cash" payment button is greyed out with tooltip: "Open a till session to accept cash."
- **Foreign currency toggle:** A "Foreign Currency" button on the payment screen opens a secondary input for the foreign amount and exchange rate, with the converted base amount shown before confirmation.
- **Paid-out shortcut:** Accessible from the till management screen (not the main POS) — requires manager-level authentication.
- **Cash-up wizard:** Step-by-step UI guiding staff through denomination count (optional) → total declaration → summary comparison → close confirmation.

---

## 18. Appendix — Environment Variables Reference

```ini
# Application
APP_NAME="NexStay"
APP_ENV=production
APP_KEY=                          # php artisan key:generate
APP_DEBUG=false
APP_URL=https://app.nexstay.io

# Database
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=nexstay
DB_USERNAME=nexstay
DB_PASSWORD=

# Redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=

# Queue
QUEUE_CONNECTION=redis
HORIZON_PREFIX=nexstay

# Broadcasting (Reverb)
BROADCAST_CONNECTION=reverb
REVERB_APP_ID=
REVERB_APP_KEY=
REVERB_APP_SECRET=
REVERB_HOST=localhost
REVERB_PORT=8080
REVERB_SCHEME=https

# Search
SCOUT_DRIVER=meilisearch
MEILISEARCH_HOST=http://localhost:7700
MEILISEARCH_KEY=

# Storage
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=eu-west-1
AWS_BUCKET=nexstay-media

# Mail
MAIL_MAILER=smtp
MAIL_HOST=smtp.sendgrid.net
MAIL_PORT=587
MAIL_USERNAME=apikey
MAIL_PASSWORD=                    # SendGrid API key

# SMS (Africa's Talking)
AT_API_KEY=
AT_USERNAME=
AT_SENDER_ID=NexStay

# Payment Gateways
STRIPE_KEY=
STRIPE_SECRET=
STRIPE_WEBHOOK_SECRET=

FLUTTERWAVE_PUBLIC_KEY=
FLUTTERWAVE_SECRET_KEY=
FLUTTERWAVE_WEBHOOK_SECRET=

AZAMPAY_APP_NAME=
AZAMPAY_CLIENT_ID=
AZAMPAY_CLIENT_SECRET=
AZAMPAY_WEBHOOK_SECRET=

# TRA VFD (Tanzania Fiscalization)
TRA_VFD_ENDPOINT=
TRA_VFD_TOKEN=
TRA_VFD_CERT_PATH=
TRA_VFD_GC=                       # GC sequence number

# Multi-tenancy
TENANCY_DATABASE_TEMPLATE=
CENTRAL_DOMAINS=nexstay.io,admin.nexstay.io

# Monitoring
SENTRY_LARAVEL_DSN=
TELESCOPE_ENABLED=false           # Enable only in staging

# Till / Cash Handling
TILL_VARIANCE_THRESHOLD=2000        # Alert threshold for over/short in base currency units
TILL_AUTO_RECONCILE_ZERO=true       # Auto-reconcile sessions with zero variance
TILL_REQUIRE_MANAGER_PIN=true       # Require manager PIN for paid-out and bank drop

# Foreign Currency
FX_RATE_SOURCE=manual               # manual | xe_api | central_bank
XE_API_KEY=                         # Required if FX_RATE_SOURCE=xe_api

# Feature flags (Laravel Pennant)
FEATURE_MOBILE_KEY=false
FEATURE_DYNAMIC_PRICING=false
FEATURE_LOYALTY=false
```

---

*NexStay TRD v1.0 — May 2026*
*Next review: June 2026 | Owner: Engineering Architecture Team*
