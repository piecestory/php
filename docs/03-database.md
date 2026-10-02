# PIECE & STORY — Database

Phase 3 deliverable. Migrations in `database/migrations`, models in `app/Domain/<Module>/Models`.

## Tables by module

| Module | Tables | Key rules |
|---|---|---|
| Identity | `users`, `addresses`, `one_time_passwords`, permission tables | email **or** phone (both unique, nullable); password nullable for OTP-only accounts; OTP codes stored hashed |
| Catalog | `categories`, `products`, `origins`, `eras`, `materials`, `material_product`, `collections`, `collection_product` | SKU and both slugs unique; category with products cannot be hard-deleted (`restrict`); `search_text` FULLTEXT, normalized for Arabic |
| Media | `media` (spatie) | product gallery ordered; first image = primary; finder reference photos on a private disk |
| Store | `branches` | showrooms are pickup points; warehouse is not |
| Wishlist | `wishlist_items` | one row per user + product |
| Cart | `carts`, `cart_items` | guest carts by token, one cart per user; **no prices stored** |
| Shipping | `shipping_methods`, `shipments` | delivery disabled until pricing is decided; branch pickup active |
| Orders | `orders`, `order_items` | address and product snapshots; customer deletion keeps the order (`set null`); `type` (purchase / reservation / deposit_reservation), `access_token` (unique, opens the order page), `deposit_total`, `amount_paid`, `hold_expires_at` + `reserved_until` + `reminded_at` (DATETIME), index (status, hold_expires_at) |
| Inventory | `stock_reservations`, `inventory_movements` | reservations expire; movements are an append-only ledger |
| Payments | `payments`, `payment_webhook_events`, `refunds` | money tables use `restrict`; webhook events unique per provider + event id; `payments.purpose` = full / deposit / balance |
| Workflow | `status_changes` | one audit trail for orders, finder requests, etc. |
| Personal Finder | `finder_requests` | no login required; unique reference |
| Auctions | `auctions`, `auction_lots`, `auction_interests` | showcase + "register interest" only; bids not modelled |
| Content | `posts`, `pages`, `hero_slides`, `faqs`, `contact_messages` | |
| Settings | `settings` | unique group + key; secrets encrypted at rest |
| Audit | `activity_log` (spatie) | admin changes (wired in Phase 11) |

## Conventions
- Money: `DECIMAL(12,2)`, VAT inclusive; arithmetic with `bcmath`, never floats.
- Bilingual text: `<field>_ar` / `<field>_en`, Arabic fallback via `HasTranslations`.
- Status columns are short strings backed by PHP enums (`app/Domain/*/Enums`), so a new status never needs a schema change.
- Required business dates use `DATETIME` (see ADR-010).
- Polymorphic columns store stable aliases (`product`, `order`, …), never class names (ADR-011).

## Reference data (`php artisan db:seed`)
Idempotent and production-safe: 4 staff roles with their permissions, the 6 site-map categories, the 3 branches, 2 shipping methods. No demo products, users or passwords are seeded. Demo catalogue data (clearly marked) arrives with the image system in Phase 6.

## Verified
- `migrate` → `migrate:reset` → `migrate` completes cleanly (50 tables).
- Seeders produce identical counts when run twice.
- Constraint behaviour covered by tests: unique SKU/phone, restricted category deletion, order lines surviving product deletion, orders surviving customer deletion, encrypted settings.
