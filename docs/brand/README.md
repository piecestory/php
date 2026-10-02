# PIECE & STORY — Brand Identity

## The mark
An interlaced **P** and **S** — the S passes over the P, as a story wraps around a piece. The monogram sits in an open ring; a laurel branch closes its left side, the classical sign of heritage and distinction. It reinterprets the original concept logo (`logo-reference.webp`) as a flat vector that stays sharp from a 16px favicon to a shop sign.

## Files (`logo/`)
| File | Use |
|---|---|
| `stacked-*` | Primary logo: packaging, social profiles, print, certificates |
| `horizontal-ar-*` | Arabic website header, invoices, email signatures |
| `horizontal-en-*` | English website header |
| `compact-ar-*` | Small headers (mobile) where the English line would be too small |
| `emblem-*` | Mark alone: stamps, seals, social avatars, watermarks |
| `monogram-*` | Letters only, no ring: very small spaces |
| `app-icon.svg`, `png/app-icon-512.png` | Phone home-screen and app icons |
| `png/` | High-resolution PNGs with transparent background |

Colour suffixes: `gold` (on ivory/white), `gold-light` (on black), `ink` (single colour), `white` (on photos and dark colours).

## Colours
| Name | Hex | Use |
|---|---|---|
| Gold | `#9A7338` | Logo on light backgrounds |
| Light gold | `#C9A46A` | Logo on black / dark backgrounds |
| Ink | `#1C1612` | Single-colour and black-and-white printing |
| Ivory | `#F7F2EA` | Preferred light background |

For printed foil (hot-stamping), use the `ink` file as the foil artwork.

## Typefaces
English: Cinzel (wordmark), Cormorant Garamond (monogram letters). Arabic: Amiri Bold. All under the SIL Open Font License — free for commercial use, including in logos.

## Rules
- **Clear space:** keep empty space around the logo of at least the height of the P.
- **Minimum size:** stacked logo 120px or 30mm wide; horizontal logo 32px or 8mm high; emblem 24px.
- **Don't:** stretch the logo, change the colours, add shadows or glow, rotate it, or place it on a busy photo without a dark overlay.

## Regenerating
The logo is generated from source in `tools/brand/`, so any refinement is exact and repeatable:
```bash
cd tools/brand && npm install && npm run export
```
