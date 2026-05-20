# NexStay — Missing Features Gap Analysis
**Reference System:** Hotel Smart System (rise-hotel.abletech.co.tz)
**Analysis Date:** May 2026
**Status:** IMPLEMENTED (May 2026) — see modules in tenant UI & API

---

> This document lists every feature present in the Hotel Smart System (HSS) that is not yet
> implemented or specified in NexStay. Each gap has its own dedicated spec file linked below.
> Priority ratings are based on operational impact for East African hotel properties.

---

## Summary

| # | Gap | Priority | Effort | Spec File |
|---|---|---|---|---|
| 1 | Purchases Module (Bar + Kitchen) | ✅ Done | `/purchases` | PO + receive → stock movements |
| 2 | Expenditures / Expense Tracking | ✅ Done | `/expenditures` | CRUD by category |
| 3 | Ancillary / Extra Services | ✅ Done | `/ancillary-services` | Catalog + folio charge |
| 4 | Debts / Outstanding Balances View | ✅ Done | `/debts` | Open folio balances |
| 5 | Damage Tracking | ✅ Done | `/damages` | Report + resolve |
| 6 | Time Left / Checkout Countdown View | ✅ Done | `/time-left` | Checked-in countdown |
| 7 | Booked List Quick View | ✅ Done | `/booked-list` | Confirmed/inquiry arrivals |
| 8 | Split Food vs. Drinks Reports | ✅ Done | `/reports/fb-revenue` | Restaurant vs bar/lounge |

---

## Coverage Status Key

| Symbol | Meaning |
|---|---|
| 🔴 | Completely missing — no schema, no spec, no route |
| 🟡 | Partially covered — schema exists but no module/UI/API spec |
| 🟢 | Minor gap — covered in principle, missing specific view or split |

---

## What NexStay Already Does Better

These are features NexStay has that HSS does not — for context when presenting to stakeholders.

- Kitchen Display System (KDS) with real-time order push
- OTA channel manager (Booking.com, Expedia, Airbnb)
- Multi-tenant architecture (multiple properties on one platform)
- Full till & cash drawer management with shift reconciliation
- Real-time WebSocket dashboard (occupancy, folio charges, room status)
- Self check-in and mobile room key
- Drink Lounge module (bottle service, minimum spend, guest list)
- Dynamic pricing engine (rule-based rate uplift by occupancy)
- Azam Pay / mobile money gateway integration
