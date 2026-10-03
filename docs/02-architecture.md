# PIECE & STORY — Architecture

Phase 2 deliverable. Business context and the full plan live in [01-project-analysis.md](01-project-analysis.md).

## Stack (verified versions)

| Layer | Choice | Version |
|---|---|---|
| Runtime | PHP | 8.4 minimum (`config.platform.php = 8.4.1` keeps dependencies installable on Hostinger) |
| Framework | Laravel | 13.x |
| Interactivity | Livewire + Alpine.js | Livewire 4.x |
| Admin | Filament | 5.x |
| Roles | spatie/laravel-permission | 8.x |
| Media | spatie/laravel-medialibrary | 11.x |
| Audit trail | spatie/laravel-activitylog | 5.x |
| CSS / build | Tailwind CSS 4 + Vite | built in CI, never on the server |
| Tests | Pest 5 (+ Playwright for E2E from Phase 4) | |
| Quality | Pint (Laravel preset + strict types), Larastan level 6 | |
| Database | MySQL 8.4 / MariaDB 10.11 (CI runs both) | local dev: MariaDB on port 3307 |

## Architecture Decision Records

### ADR-001 — Modular monolith
One Laravel app. Business modules live in `app/Domain/<Module>/` (Models, Enums, Actions, Events, Policies, Data). Delivery code (`app/Http`, `app/Livewire`, `app/Filament`) calls Actions; it never holds business rules. Enforced by `tests/Arch/ArchitectureTest.php`: `App\Domain` may not depend on HTTP, Livewire or Filament. Module folders are created when the module is built — no empty scaffolding.

### ADR-002 — Actions for business operations
Each use case is one invokable class (e.g. `PlaceOrder`, `TransitionOrderStatus`). The storefront and the admin call the same Action, so rules exist once. Cross-module side effects go through events (`OrderPlaced` → notifications, stock).

### ADR-003 — Bilingual columns, not JSON
Exactly two locales (ar default, en). Translated fields are explicit columns (`name_ar`, `name_en`) so they can be indexed, searched with FULLTEXT and validated individually. Accessed through one shared trait, not duplicated per model.

### ADR-004 — Money
`DECIMAL(12,2)` in the database, never floats in PHP arithmetic. Currency is SAR only (business decision). Prices are VAT-inclusive (15%); VAT is extracted for invoices, not added.

### ADR-005 — Shared-hosting runtime
- Queue: `database` driver, drained by `schedule:run` (single cron entry, every minute) with `--stop-when-empty`.
- Cache: `database` (swap to Redis later via `.env` only).
- Sessions: `database`, encrypted, secure cookies in production.
- No WebSockets: features needing push (live bidding) are deferred.
- Assets are built in CI and shipped compiled.

### ADR-006 — Payments behind an interface
`PaymentGateway` contract with one driver per provider. Required methods (business decision): mada, Apple Pay, Tabby, Tamara. Cards/mada/Apple Pay go through one card gateway; Tabby and Tamara are separate BNPL drivers. Sandbox keys only until launch. Webhooks are idempotent (unique `provider + event_id`).

### ADR-007 — Guest checkout
Guests can buy without an account. A mobile number is required at checkout (couriers need it to deliver); email is optional, so the order confirmation is sent by email when one is given. BNPL providers may ask for more details inside their own flow.

### ADR-008 — Safety defaults (`AppServiceProvider`)
- Strict Eloquent outside production: lazy loading (N+1), missing attributes and silently discarded mass-assignment throw.
- `CarbonImmutable` everywhere.
- Destructive DB commands are blocked in production.
- HTTPS forced in production.
- Password policy: minimum 10 characters, letters and numbers in production. No external breach-check service.

### ADR-009 — Timezone
`Asia/Riyadh` (UTC+3, no DST), so stored and displayed times match the business day.

### ADR-010 — DATETIME for required business dates
MySQL/MariaDB servers running with `explicit_defaults_for_timestamp=OFF` (common on older shared hosts) reject a second NOT NULL `TIMESTAMP` column and silently add `ON UPDATE CURRENT_TIMESTAMP` to the first. Expiry, auction start/end and ledger dates therefore use `DATETIME`, which behaves identically everywhere. Nullable `TIMESTAMP`s (Laravel's `created_at`/`updated_at`) are unaffected.

### ADR-011 — Enforced morph map
Polymorphic columns (`media`, `status_changes`, `model_has_roles`, …) store short aliases registered in `AppServiceProvider`. Classes can be moved or renamed without rewriting data.

### ADR-012 — One status audit trail
`status_changes` + the `HasStatusHistory` trait record every workflow transition (orders, finder requests) with author and note, instead of one history table per module.

### ADR-013 — Full-text search tests commit
InnoDB FULLTEXT indexes only see committed rows. Tests that exercise search live in `tests/Integration` and use `DatabaseTruncation`; all other feature tests roll back.

### ADR-014 — Locale in the URL
`routes/storefront.php` is registered twice by `routes/web.php`: Arabic at `/` (plain route names) and English at `/en` (`en.` names). `SetLocale` takes the language from the route, never from session. Use `localized_route('name')` in views; `LocalizedRoute::switchTo()` builds the language switch and `hreflang` links.

### ADR-015 — Navigation shows only built pages
`App\View\Navigation` lists menu entries in order; an entry appears only when its route exists. Product-card actions (wishlist, cart) follow the same rule. No placeholder links or non-working buttons ship.

### ADR-016 — Authentication
Own thin controllers over Laravel primitives (no Fortify): email-or-mobile + password, password reset, and SMS one-time codes behind the `SmsGateway` interface. Mobile sign-in returns 404 until `sms.enabled` is switched on from admin settings. Codes are hashed, expire after 5 minutes, die after 5 wrong attempts; per-number and per-IP request limits. Named rate limiters (`auth-forms`, `lookups`) count per form and visitor.

### ADR-017 — CSP-ready front end
Livewire/Alpine use the CSP build bundled through Vite; interactive behaviour is registered as named `Alpine.data` components in `resources/js/app.js`. No inline scripts or handlers, so a strict Content-Security-Policy can be enabled in Phase 15.

### ADR-018 — Localized slugs
Catalog models use `HasLocalizedSlug`: route binding and URL generation use `slug_ar` or `slug_en` for the active language. `SetLocale` runs before `SubstituteBindings` (middleware priority) so `/en/store/lighting` resolves against `slug_en`. `LocalizedRoute::url()` switches the language while generating, so cross-language links (hreflang, emails) carry the right slug. A slug in the wrong language is a 404, never a duplicate page.

### ADR-019 — Images
`HasWebpRenditions` (wraps the media library) creates WebP renditions per model (`IMAGE_SIZES`) in the background queue; views get `src`/`srcset` through `ResponsiveImage`, which serves the original until renditions exist. Production needs `php artisan storage:link` and the cron-driven queue for renditions to appear.

### ADR-019b — Exact srcset widths
Renditions are width-bound and never upscaled; `RecordImageDimensions` stores each upload's pixel size so srcset descriptors state the real width. iPhone HEIC files are not supported by GD: the admin upload must accept JPEG/PNG/WebP (browsers convert HEIC on upload) — see Phase 11.

### ADR-020 — Catalog queries
`BrowseProducts` builds every listing (store, category, collection, search) from a `CatalogFilters` value object. Price filters and sorting use the price actually charged (active sale price), mirrored in SQL and in `Product::isOnSale()`. Search: normalized words with Arabic prefixes stripped, InnoDB boolean FULLTEXT with prefix matching; words shorter than 3 letters fall back to `LIKE`. Invalid query-string values are dropped, not shown as errors, so shared links never break.

### ADR-021 — Sample catalogue
`DemoCatalogSeeder` (never part of `DatabaseSeeder`, refuses to run in production) creates 24 pieces with `DEMO-` SKUs and sample descriptions for staging. `php artisan catalog:remove-demo` deletes them and their images before launch.

### ADR-022 — Cart and wishlist
- Member: one cart row per user. Guest: a cart row found by a random 40-char token in an encrypted, HTTP-only cookie (`ps_cart`), created only when the first piece is added (refusals never create carts). Guest carts expire after `store.guest_cart_days` of inactivity and are pruned daily.
- Prices are never stored in the cart; `CartSummary` reads the live price and lists pieces that became unavailable separately (excluded from the total). VAT is extracted from VAT-inclusive prices (`store.vat_rate`).
- Wishlist: database for members, session for guests. At sign-in (`Login` event) the guest cart and wishlist merge into the account, quantities capped at stock.
- Buttons are real forms (work without JavaScript); `shopForm` sends them in the background and publishes `cart-updated` / `wishlist-updated` / `notify` window events for the header counters and the toast. Forms are never nested: the catalog filter form wraps only the sidebar.

### ADR-023 — Checkout, stock holds and order numbers
- One-page checkout (`CheckoutController` + `CheckoutRequest`): contact (mobile required, email optional), pickup showroom or Saudi national address (only active shipping methods are offered), buy now / reserve, payment method. Required fields follow the choices; without JavaScript every section is shown and the server applies the same rules.
- `PlaceOrder` runs in one transaction: products are locked `FOR UPDATE` in id order (no deadlocks, no double sale of a unique piece), re-checked, snapshotted into `order_items`, and taken out of stock immediately (a **hold**, recorded in `inventory_movements` and `stock_reservations`). The cart is emptied; the order page carries the customer from there.
- Holds end automatically: unpaid checkouts after `store.payment_hold_minutes` (15), reservations after 4 + 3 days. `orders:expire-holds` (every minute) cancels them through `CancelOrder`, which puts the pieces back and refunds anything captured. A held piece shows as "reserved", not "sold".
- Order numbers: `PS-<year>-<id, 6 digits>`. Every order has a random 40-char `access_token`: the order page (`/orders/{number}?key=`) opens only with it or for the signed-in owner (404 otherwise, so numbers cannot be probed). The tracking page hands it over once number + mobile match.
- Status changes only through `TransitionOrder` (validated against `OrderStatus::allowedTransitions`, written to `status_changes`). New status `reserved`: pending → reserved → confirmed / cancelled.
- Controllers resolve per-request services (`CurrentCart`, `CurrentWishlist`) per call, never in constructors: the router caches controller instances.

### ADR-024 — Payments behind a gateway interface
- `PaymentGateway` (domain contract): `start` (redirect to the provider's page), `result` (ask the provider), `webhook` (verify signature, parse), `refund`. `PaymentGateways` maps each offered method (mada, Apple Pay, Tabby, Tamara — credit cards not offered) to the first gateway supporting it; methods with no gateway are not shown. Real providers (Moyasar, Tabby, Tamara) plug in here once their accounts exist; nothing else changes.
- The browser's return is never trusted: the outcome is read from the provider (`result`) or from a signed webhook. `SettlePayment` locks the payment row and is idempotent (return + webhook + retries apply once); a captured amount or currency that differs from the payment is rejected. `payment_webhook_events` (unique provider + event id) makes repeated notifications no-ops.
- Money that arrives for an order that can no longer take it (expired, already paid) is refunded automatically and recorded in `refunds`; a failed refund is kept as `failed` for manual follow-up.
- `SandboxGateway` (infrastructure) simulates a hosted payment page with approve / decline buttons and HMAC-signed webhooks. It is registered only when `PAYMENT_SANDBOX=true` **and** the environment is not production. No card data is ever requested.

### ADR-025 — Advance reservation
- Owner's rules: reservation lasts 4 days, then 3 days of daily payment reminders, then automatic cancellation. Optional deposit of 15% of the total (rounded half-up to the halala), refunded in full on cancellation. Configurable in `config/store.php` (`reservation.*`).
- Without deposit: the order is `reserved` at once. With deposit: `pending` for the payment window, then `reserved` when the deposit is captured (hold extended to 4 + 3 days). Deposits are card payments (mada / Apple Pay); instalment plans can pay the balance or a full purchase.
- The balance is paid online from the order page (or in the showroom; recording in-store payments arrives with the admin panel). `orders:remind-reservations` runs daily at 13:00 (start of working hours) and reminds at most once per ~day.
- Abuse guard: at most `reservation.max_open_per_phone` (2) open reservations per mobile number.
- Customers are told by SMS (always) and email (when given) when an order is confirmed, a piece is reserved, a reminder is due and a reservation is cancelled; the store mailbox (`store.email`) gets new confirmed orders and reservations. All messages are queued and sent after the transaction commits.

### ADR-026 — Customer account
- `/account` (behind `auth`): overview (orders awaiting payment first, recent orders, default address, wishlist count), order history (paginated; each order opens the existing order page, which needs no key for its owner), address book, profile and password. The wishlist keeps its own page and is linked from the account menu.
- Isolation: every record is read through the signed-in customer (`$user->orders()`, `$user->addresses()->findOrFail($id)`), never by a bare id, so another customer's address or order is a 404. Covered by tests.
- Address book: Saudi National Address, up to 10 addresses, exactly one default (first saved, or chosen); deleting the default promotes the most recent remaining one. The default prefills checkout. Address rules and digit normalisation are shared with checkout (`NationalAddress`).
- Profile: changing the email or mobile (sign-in identifiers) needs the current password when the account has one; a changed identifier loses its verified status. Accounts with a password keep an email (it is how they sign in). Customers who signed up with an SMS code can set a first password. Changing the password rotates the remember-me token and ends the customer's other sessions.
- Message language (`users.locale`) is chosen in the profile; emails and SMS follow it.

### ADR-027 — Staff panel (Filament 5, `/admin`)
- Arabic, RTL, brand bronze (explicit OKLCH palette) and IBM Plex Sans Arabic bundled locally (`resources/css/filament/admin/theme.css`, built by Vite). Filament's own assets are published by `php artisan filament:upgrade` (composer post-autoload-dump) and are not committed.
- The storefront bundles Livewire's CSP-safe build and turns automatic asset injection off; the panel adds Livewire's standard assets on its own pages through render hooks. Consequence for Phase 15: `/admin` needs its own, looser Content-Security-Policy (Filament evaluates Alpine expressions).
- Access: `User::canAccessPanel()` = active account with `access_admin`. Each section declares the one permission it needs (`RequiresPermission` trait over Filament's authorization) and lists abilities it never offers (orders cannot be created, edited or deleted by hand; pieces are archived, not erased; branches and shipping methods are switched off, not deleted). The role → permission matrix lives in `Role::permissions()` and is covered by a test that opens every section with every role.
- Screens only call domain actions: `StockLedger::setQuantity` (opening stock and adjustments, with reason and author, in the movement log), `RecordInStorePayment`, `FulfilOrder` (prepare → ship with tracking → delivered; showroom pickups go prepare → delivered), `CancelOrder`, `SaveStaffMember` (nobody changes their own role or switches themselves off; at least one active system admin always remains), `RevokeSessions` (deactivated accounts and password changes sign the user out everywhere).
- Showroom payments are recorded as provider `in_store`; cancelling such an order creates a *pending* refund (money is returned at the showroom), never a fake "completed" one.
- Admin URLs address records by id (`$recordRouteKeyName = 'id'`), so editing a storefront slug never moves an admin page.
- Change log: catalogue, content and settings models use `RecordsChanges` (spatie/activitylog: only changed fillable fields, with the staff member as causer); stock is excluded because it has its own ledger. Read-only "سجل التعديلات" for system admins.
- First admin on a server: `php artisan admin:create` (hidden password prompt; nothing in code or shell history).

## Directory map

```
app/
  Domain/<Module>/   business logic (created per phase)
  Http/              controllers, form requests, middleware
  Livewire/          interactive storefront components
  Filament/          admin panel
  Support/           cross-cutting helpers (money, localization, SEO)
docs/                project documentation and brand references
tests/Arch           architecture rules
tests/Unit           pure logic
tests/Feature        HTTP / database behaviour (rolled back per test)
tests/Integration    behaviour needing committed data (full-text search)
.github/workflows    CI: lint, static analysis, audit, tests on MySQL + MariaDB
```

## Commands

| Purpose | Command |
|---|---|
| Lint (check) | `composer lint` |
| Auto-format | `composer format` |
| Static analysis | `composer analyse` |
| Tests | `composer test` |
| All of the above | `composer check` |
