# Phase 17 — QA report

Exit criterion: CRITICAL = 0, HIGH = 0. **Met.**

## What was checked

| Area | How | Result |
|---|---|---|
| Automated suite | `composer check` (Pint, Larastan 6, Pest) | all green |
| Customer journey | Playwright: search → wishlist → cart → checkout → sandbox payment approved / declined → order page | pass |
| Staff panel | Playwright sign-in → orders → order; every create/edit/detail screen renders with real data (Pest) | pass |
| Responsive | 13 pages × 9 widths (320–1920) × AR/EN: no horizontal overflow | pass |
| Accessibility | axe-core WCAG 2.1 A/AA on 12 pages × AR/EN | pass (after contrast fix in phase 16) |
| Visual | Screens reviewed: home, store, category, product (desktop + phone), collection, search, about, services, FAQ, contact, cart/checkout empty state, auctions (empty), track order, 404 (AR/EN), account (overview, orders, addresses, profile), admin (dashboard, orders, order, product edit, new auction, staff) | no defects beyond the list below |
| Emails | Order confirmed / reserved / shipped rendered in Arabic and English | one defect (fixed, below) |
| Error pages | 401, 402, 403, 404, 419, 429, 500, 503 and generic 4xx/5xx exist and use the store layout | ok |
| Security | Phase 15 headers, CSP (no violations while browsing storefront and admin), audits clean | ok |

## Findings

| # | Severity | Finding | Status |
|---|---|---|---|
| 1 | MEDIUM | Shipping without a tracking number (allowed, e.g. own delivery) sent "Tracking number: ." in the email and SMS | **Fixed**: the tracking sentence is included only when there is a number; regression test added |
| 2 | MEDIUM | Performance budget: Arabic home (≈3.5 s) and product page (≈3.0 s) LCP over 2.5 s in strict local throttling | Open — re-measure on production (HTTP/2, opcache) in phase 18; see `docs/qa/phase-14/web-vitals.md` |
| 3 | LOW | The internal test-payment page is Arabic only | Accepted — never active in production |
| 4 | LOW | Al-Harazat showroom has no address or map link, and showroom addresses appear in Arabic on English pages | Waiting for the owner's showroom details (open item in §9.2) |

No CRITICAL or HIGH findings.
