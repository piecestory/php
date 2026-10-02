# PIECE & STORY — Design System

Phase 4 deliverable. Tokens live in `resources/css/app.css`; components in `resources/views/components`.
Local reference page: `/_design` (`?lang=en` for English) — registered only in the local environment.
Screenshots: `docs/qa/phase-4/`.

## Tokens
| Group | Tokens |
|---|---|
| Surfaces | `ivory` page · `paper` cards · `linen` secondary · `line` / `line-strong` borders |
| Text | `ink` primary · `ink-soft` secondary (AA on all surfaces) · `ink-faint` decoration/large text only |
| Brand | `bronze` actions (white text 4.7:1) · `bronze-deep` hover/sale price · `gold` ornaments and text on dark only · `gold-deep` logo |
| Dark | `night`, `night-soft` (auction card, top bar, footer) |
| Radius | 2 / 4 / 6 px — classical, never pill-shaped except icon buttons |
| Motion | 150–300 ms, `ease-elegant`; disabled under `prefers-reduced-motion` |

## Typography (self-hosted, no font CDN)
| Role | Arabic | English |
|---|---|---|
| Display (h1–h4) | Alexandria | Cormorant Garamond |
| Body / UI | IBM Plex Sans Arabic | IBM Plex Sans Arabic (Latin glyphs = IBM Plex Sans) |
| Prices | Latin numerals, `numerals` utility isolates them inside RTL text | |

Fluid sizes: `text-display-xl/lg/md/sm`.

## Components
| Component | Notes |
|---|---|
| `x-ui.button` | primary · dark · outline · light · ghost · link; sm/md/lg; link or button; icon-only with accessible label |
| `x-ui.icon` | curated Lucide set in `resources/svg/icons`; arrows/chevrons mirror automatically in RTL |
| `x-logo` | horizontal (per language) · compact · emblem; inherits text colour; unique SVG ids per instance |
| `x-ui.ornament` | the brand's gold rule |
| `x-ui.section-heading` | title + optional "view all" link |
| `x-ui.image` | fixed aspect ratio (no layout shift), lazy by default, `eager` for the hero, brand placeholder when missing or broken |
| `x-ui.price` | SAR formatting from decimal strings; sale price + struck original |
| `x-ui.badge` | rare · sale · sold · reserved · neutral |
| `x-ui.breadcrumbs`, `x-ui.alert`, `x-ui.empty-state` | |
| `x-product-card`, `x-category-tile`, `x-promo-card`, `x-trust-item` | storefront building blocks from the reference design |
| `x-form.input/select/textarea/checkbox` | label, required marker, hint, error linked via `aria-describedby`, old input restored |

## Rules
- Layout uses logical properties only (`ms/me/ps/pe/start/end`) so one template serves RTL and LTR.
- Assets are compiled: after changing classes run `npm run build` (or `npm run dev` while developing).
- No component may hard-code user-facing text; strings come from `lang/{ar,en}`.

## Verified (Phase 4)
- No horizontal overflow or off-screen elements at 320, 375, 768, 1440 and 1920 px in Arabic and English.
- Contrast: body and secondary text ≥ 4.5:1; struck "old price" moved from faint to secondary tone to meet AA.
- 18 automated view tests: RTL/LTR document direction, price formatting, image fallback and lazy loading, accessible form errors, icon mirroring and path safety, unique logo ids, design page hidden outside local.
