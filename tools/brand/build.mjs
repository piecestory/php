import { monogram } from './mono.mjs';
import { textPath, bbox } from './textpath.mjs';


const MONO = { pdx: -20, sSize: 340, sdx: 0, sdy: 85, gap: 7 };
const R = 182;           // ring radius
const EMBLEM_R = R + 34; // ring + laurel reach
const f = (n) => +n.toFixed(2);

// Emblem centred on (0,0)
function emblem(id, color, { ring = 'laurel' } = {}) {
  const m = monogram({ ...MONO, id, color, ring, cx: 'auto', ringR: R });
  // Re-run so the ring/laurel use the auto centre computed from the letters
  const m2 = monogram({ ...MONO, id, color, ring, cx: m.cx, cy: m.cy, ringR: R });
  return { defs: m2.defs, body: `<g transform="translate(${f(-m.cx)} ${f(-m.cy)})">${m2.body}</g>`, box: m.box, cx: m.cx, cy: m.cy };
}

function text(fontKey, str, size, opts) {
  const t = textPath(fontKey, str, size, opts);
  return { ...t, b: bbox(t.d) };
}
const place = (t, x, y, color) => `<path fill="${color}" transform="translate(${f(x - t.b.x0)} ${f(y)})" d="${t.d}"/>`;

// Ornamental rule: two hairlines with a small lozenge and dots in the centre
function rule(cx, y, width, color, w = 2.2) {
  const g = 34, d = 9;
  return `<g fill="${color}">
    <rect x="${f(cx - width / 2)}" y="${f(y - w / 2)}" width="${f(width / 2 - g)}" height="${w}"/>
    <rect x="${f(cx + g)}" y="${f(y - w / 2)}" width="${f(width / 2 - g)}" height="${w}"/>
    <path d="M${f(cx)} ${f(y - d)} L${f(cx + d)} ${f(y)} L${f(cx)} ${f(y + d)} L${f(cx - d)} ${f(y)}Z"/>
    <circle cx="${f(cx - 21)}" cy="${f(y)}" r="3.2"/><circle cx="${f(cx + 21)}" cy="${f(y)}" r="3.2"/></g>`;
}

const svg = (vb, inner, defs = '', title = 'Piece &amp; Story — قطعة وقصة') =>
  `<svg xmlns="http://www.w3.org/2000/svg" viewBox="${vb.map(f).join(' ')}" role="img" aria-label="${title}"><title>${title}</title>${defs ? `<defs>${defs}</defs>` : ''}${inner}</svg>\n`;

export function build(color, idp = 'ps') {
  const out = {};
  // 1. Emblem
  {
    const e = emblem(idp + 'e', color);
    out.emblem = svg([-EMBLEM_R, -EMBLEM_R, EMBLEM_R * 2, EMBLEM_R * 2], e.body, e.defs);
  }
  // 2. Monogram only (for favicon / small marks)
  {
    const e = emblem(idp + 'm', color, { ring: 'none' });
    const w = e.box.x1 - e.box.x0, h = e.box.y1 - e.box.y0, s = Math.max(w, h) / 2 + 14;
    out.monogram = svg([-s, -s, s * 2, s * 2], e.body, e.defs);
  }
  // 3. Stacked lockup
  {
    const e = emblem(idp + 's', color);
    const en = text('cinzel', 'PIECE & STORY', 74, { tracking: 0.13 });
    const ar = text('amiri', 'قطعة وقصة', 92);
    const W = Math.max(en.b.w, 2 * EMBLEM_R) + 40;
    const yEn = EMBLEM_R + 40 + en.b.h; // baseline
    const yRule = yEn + 42;
    const yAr = yRule + 40 - ar.b.y0;
    const inner = e.body + place(en, -en.b.w / 2, yEn, color) + rule(0, yRule, en.b.w * 0.82, color) + place(ar, -ar.b.w / 2, yAr, color);
    const bottom = yAr + ar.b.y1 + 16;
    out.stacked = svg([-W / 2, -EMBLEM_R - 16, W, bottom + EMBLEM_R + 16], inner, e.defs);
  }
  // 4/5. Horizontal lockups
  for (const dir of ['ar', 'en']) {
    const e = emblem(idp + 'h' + dir, color);
    const big = dir === 'ar' ? text("amiri", "قطعة وقصة", 200) : text("cinzel", "PIECE & STORY", 118, { tracking: 0.1 });
    const small = dir === 'ar' ? text("cinzel", "PIECE & STORY", 50, { tracking: 0.3 }) : text("amiri", "قطعة وقصة", 96);
    const blockW = Math.max(big.b.w, small.b.w);
    const gap = 54, H = EMBLEM_R * 2;
    // vertical rhythm: big text and small text centred as a block on the emblem's centre
    const lineGap = 30;
    const blockH = big.b.h + lineGap + small.b.h;
    const top = -blockH / 2;
    const yBig = top - big.b.y0;
    const ySmall = top + big.b.h + lineGap - small.b.y0;
    let inner, x0, width;
    if (dir === 'ar') {
      // emblem on the right, text to its left (RTL reading order)
      const textRight = -EMBLEM_R - gap, cxText = textRight - blockW / 2;
      inner = e.body + place(big, cxText - big.b.w / 2, yBig, color) + place(small, cxText - small.b.w / 2, ySmall, color);
      x0 = textRight - blockW; width = EMBLEM_R - x0;
    } else {
      const textLeft = EMBLEM_R + gap, cxText = textLeft + blockW / 2;
      inner = e.body + place(big, cxText - big.b.w / 2, yBig, color) + place(small, cxText - small.b.w / 2, ySmall, color);
      x0 = -EMBLEM_R; width = textLeft + blockW + EMBLEM_R;
    }
    out['horizontal-' + dir] = svg([x0 - 8, -H / 2 - 8, width + 16, H + 16], inner, e.defs);
  }
  // 6. Compact Arabic lockup for small headers (no secondary line)
  {
    const e = emblem(idp + 'c', color);
    const ar = text('amiri', 'قطعة وقصة', 200);
    const gap = 50, H = EMBLEM_R * 2;
    const textRight = -EMBLEM_R - gap;
    const y = -(ar.b.y0 + ar.b.y1) / 2;
    const x0 = textRight - ar.b.w;
    out['compact-ar'] = svg([x0 - 8, -H / 2 - 8, EMBLEM_R - x0 + 16, H + 16], e.body + place(ar, x0, y, color), e.defs);
  }
  return out;
}

export function favicon(bg, fg) {
  const e = emblem('fav', fg, { ring: 'none' });
  const w = e.box.x1 - e.box.x0, h = e.box.y1 - e.box.y0, s = Math.max(w, h) / 2 + 38;
  return svg([-s, -s, s * 2, s * 2], `<rect x="${f(-s)}" y="${f(-s)}" width="${f(2 * s)}" height="${f(2 * s)}" rx="${f(s * 0.22)}" fill="${bg}"/>` + e.body, e.defs);
}
