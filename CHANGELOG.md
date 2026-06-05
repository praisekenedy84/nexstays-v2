# Changelog

All notable changes to NexStay are documented here.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/).

## [Unreleased]

## [2026-06-05]

### Added

#### Authentication
- **Username login** — staff can sign in with a unique username or email on both web and API.
- `User::findByLoginIdentifier()` resolves credentials by username or email.
- `Username` helper for sanitizing, deriving from email/name, and ensuring uniqueness.
- Demo tenant credentials now display usernames instead of emails (`admin`, `frontdesk`, `housekeeper`).
- Tenant migrations backfill usernames for existing users and make email optional.

#### Role navigation
- Per-role **navigation visibility** — admins can hide sidebar items per role without changing permissions.
- `NavigationMenuRegistry` centralizes navigable menu items from `config/nexstay.php`.
- `SyncRoleNavigation` action persists `hidden_navigation_ids` on roles.
- Role form UI with a navigation checklist.
- `Role` model with `hidden_navigation_ids` cast.

#### Inventory
- **Recipe-linked stock deduction** when an order closes (`OrderClosed` event → `DeductInventoryOnOrderClose` listener).
- `InventoryDeductionService` deducts from recipe ingredients; logs shortages when stock goes negative.
- **Restock workflow** — `RestockStockItem` action records movements, updates stock, and tracks who restocked and when.
- **Beverage stock linking** — bar menu items can be linked to stock items via `BeverageStockLinkService`.
- `awaiting_stock` flag for placeholder stock rows created before first delivery.
- `SyncMenuItemRecipe` action keeps recipe ingredients in sync with linked stock.
- Inventory index: low-stock count, awaiting-stock section, unlinked bar menu items alert.
- Restock form and audit fields (`last_restocked_at`, `last_restocked_by`).
- Menu item form: link bar items to stock, sync bar inventory bulk action.
- Stock and menu JSON endpoints for typeahead/search in forms.

#### F&B orders
- **FB order list and detail pages** (`FbOrderController`) with date range, outlet, waiter, status, and search filters.
- Sales summary on the order index (totals, payment breakdown).
- **Delete settled orders** via `DeleteOrder` action with settlement reversal.
- **Cancel closed orders** — `OrderService::cancel()` reverses folio/till settlements and restores beverage stock.
- `OrderSettlementReversalService` for undoing charges and payments on voided settled orders.

#### Platform
- Tenant creation form accepts admin username.
- `database/tenantdemo` added to `.gitignore` (local demo database artifact).

### Changed
- Login screens use a **username** field instead of email.
- User create/edit forms require username; email is optional.
- Profile edit shows username (read-only).
- `ReceivePurchaseOrder` clears `awaiting_stock` when goods are received.
- `OrderService` dispatches `OrderClosed` after commit on close.
- `HbmsNavigation` respects role-hidden navigation IDs.
- Demo seeders updated for username-based accounts.
- OpenAPI `User` schema includes `username`.

### Database
- `users.username` — unique, required; `users.email` — nullable.
- `roles.hidden_navigation_ids` — JSON nullable.
- `stock_items.menu_item_id` — optional FK to linked menu item.
- `stock_items.awaiting_stock` — boolean, default false.
- `stock_items.last_restocked_at` / `last_restocked_by` — restock audit.

### Tests
- `DualLoginTest`, `UsernameLoginTest` — auth identifier resolution.
- `RoleNavigationTest` — per-role nav hiding.
- `RestockStockItemTest`, `BeverageStockLinkTest`, `BarInventorySyncTest`, `OrderInventoryTest` — inventory flows.
- `CancelClosedOrderTest`, `DeleteOrderTest` — order reversal and deletion.
- `LoginApiTest` updated for username-based API login.
