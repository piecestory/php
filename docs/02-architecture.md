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
