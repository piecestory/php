# PIECE & STORY — engineering notes

Luxury antiques e-commerce (Arabic-first RTL + English LTR). Laravel 13 / Livewire 4 / Filament 5 / MySQL, deployed to Hostinger Web Business (shared hosting).

- Plan and business decisions: `docs/01-project-analysis.md`. Architecture and ADRs: `docs/02-architecture.md`. Read both before changing structure.
- The owner is not a developer: make technical decisions yourself; ask only about business behaviour.
- Business logic goes in `app/Domain/<Module>/Actions`; controllers, Livewire and Filament only call Actions.
- Every PHP file declares `strict_types`. Run `composer check` (Pint, Larastan level 6, Pest) before calling anything done; never report a test as passing without running it.
- No features, pages or packages beyond the agreed plan. No secrets in code; `.env` only.

## Local environment (Windows)
- PHP extensions are enabled per project: set `PHP_INI_SCAN_DIR` to `<repo>/.dev/php` before running `php`/`composer`.
- Database: isolated MariaDB on port 3307. Start with `.dev/start-db.ps1`. Databases `piece_story` and `piece_story_test`.
- Edit files with the editor tools or Bash — PowerShell `Set-Content -Encoding utf8` writes a BOM that breaks `declare(strict_types=1)`.
- After changing Tailwind classes or JS run `npm run build` (assets are compiled; the dev server serves `public/build`).
- Staff panel at /admin (Filament). Create an admin with `php artisan admin:create`. Filament assets: `php artisan filament:upgrade` (runs on composer install).
- Local preview: `.claude/launch.json` → "app" (http://localhost:8000). Arabic at `/`, English at `/en`.
- Browser tests: `npm run test:e2e` (Playwright on installed Edge) against the running local site; they create "E2E" orders, so never point them at production.
- Releases: GitHub Actions → Deploy (staging, then production); server layout, cron and launch checklist in `docs/03-deployment.md`. Backups: `php artisan backup:run` (locally set `BACKUP_MYSQLDUMP=C:\xampp\mysql\bin\mysqldump.exe`).
