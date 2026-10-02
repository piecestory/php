# PIECE & STORY — قطعة وقصة

Luxury antiques e-commerce platform. Arabic (RTL) first, English (LTR) second.

- Plan & business decisions: [docs/01-project-analysis.md](docs/01-project-analysis.md)
- Architecture & ADRs: [docs/02-architecture.md](docs/02-architecture.md)

## Requirements
PHP 8.4+ (pdo_mysql, gd, intl, zip, exif, fileinfo), Composer 2, Node 22+, MySQL 8.4 or MariaDB 10.11+.

## Setup
```bash
composer install
cp .env.example .env   # fill DB_* values
php artisan key:generate
php artisan migrate
npm install && npm run build
```

## Quality gate
```bash
composer check
```
Runs Pint, Larastan and the Pest suite. CI runs the same checks on MySQL and MariaDB.
