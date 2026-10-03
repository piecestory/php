# Phase 14 — measured web vitals (local)

Headless Edge, Lighthouse mobile throttling (150 ms RTT, 1.6 Mbps down, CPU 4× slower), 412×823 @1.75,
cold cache, median of 3 runs. Local PHP server behind a gzip proxy, with `php artisan optimize`.
Budgets: LCP < 2.5 s, CLS < 0.1, JS ≤ 100 KB gzip.

| Page | TTFB | FCP | LCP | CLS | TBT | Transfer |
|---|---|---|---|---|---|---|
| `/` (Arabic home) | 0.6–0.8 s | 1.5–2.0 s | **3.1–3.8 s** | 0.015 | 50–155 ms | 304 KB |
| `/en` | 0.5–0.7 s | 1.6–1.9 s | 2.3–2.7 s | 0 | 110–140 ms | 194 KB |
| `/store` | 0.5–0.6 s | 1.2–1.7 s | 1.7–1.8 s | 0.001 | 247 ms | 232 KB |
| `/en/store/antiques` | 0.3–0.4 s | 1.0–1.3 s | 1.6–2.1 s | 0 | 112–138 ms | 194 KB |
| product page | 0.3–0.5 s | 1.1–1.4 s | **2.9–3.2 s** | 0 | 30–156 ms | 192 KB |
| `/blog` | 0.2 s | 0.7 s | 0.7 s | 0.02 | 68 ms | 257 KB |
| `/contact` | 0.2 s | 0.7 s | 0.7 s | 0.001 | 195 ms | 302 KB |

Before this phase (same pages, no gzip): LCP 3.8 / 4.8 / 4.2 / 5.2 s (home, store, category, product);
JS 101.5 KB gzip → 24.6 KB; HTML −58 KB per page (logos).

Over budget: Arabic home and product page (the photo shares bandwidth with ~230 KB of Arabic fonts).
Re-measure with PageSpeed Insights on the production domain (HTTP/2, opcache) before launch (phase 18).
