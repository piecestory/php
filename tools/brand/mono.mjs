import { textPath, bbox } from './textpath.mjs';

// Monogram: P with an S passing over it, separated by a knockout gap; optional laurel + ring.
export function monogram({
  pKey = 'cormBold', sKey = 'cormBoldIt', pSize = 300, sSize = 300,
  sdx = 0, sdy = 0, gap = 9, splitX = 9999, pdx = -28, flip = false, ring = 'laurel', id = 'm', color = 'currentColor',
  ringR = 190, ringW = 4.5, cx = 0, cy = -105,
}) {
  const P = textPath(pKey, 'P', pSize), S = textPath(sKey, 'S', sSize);
  const pb = bbox(P.d), sb = bbox(S.d);
  // Optical centre of the pair
  const pt = `translate(${(-pb.x0 - pb.w / 2 + pdx).toFixed(2)} 0)`;
  const st = `translate(${(-sb.x0 - sb.w / 2 + sdx).toFixed(2)} ${sdy})`;
  const shift = (b, dx, dy) => ({ x0: b.x0 + dx, x1: b.x1 + dx, y0: b.y0 + dy, y1: b.y1 + dy });
  const pB = shift(pb, -pb.x0 - pb.w / 2 + pdx, 0), sB = shift(sb, -sb.x0 - sb.w / 2 + sdx, sdy);
  const box = { x0: Math.min(pB.x0, sB.x0), x1: Math.max(pB.x1, sB.x1), y0: Math.min(pB.y0, sB.y0), y1: Math.max(pB.y1, sB.y1) };
  if (cx === "auto") { cx = (box.x0 + box.x1) / 2; cy = (box.y0 + box.y1) / 2; }
  const parts = [];
  // Interlace: left of splitX the S passes over the P; right of it the P passes over the S.
  const sx = splitX;
  parts.push(`<clipPath id="${id}-cl"><rect x="-400" y="-500" width="${400 + sx}" height="800"/></clipPath>
    <clipPath id="${id}-cr"><rect x="${sx}" y="-500" width="${400 - sx}" height="800"/></clipPath>
    <mask id="${id}-kp" maskUnits="userSpaceOnUse" x="-400" y="-500" width="800" height="800">
      <rect x="-400" y="-500" width="800" height="800" fill="#fff"/>
      <g clip-path="url(#${id}-${flip ? "cr" : "cl"})"><path transform="${st}" d="${S.d}" fill="#000" stroke="#000" stroke-width="${gap * 2}" stroke-linejoin="round"/></g></mask>
    <mask id="${id}-ks" maskUnits="userSpaceOnUse" x="-400" y="-500" width="800" height="800">
      <rect x="-400" y="-500" width="800" height="800" fill="#fff"/>
      <g clip-path="url(#${id}-${flip ? "cl" : "cr"})"><path transform="${pt}" d="${P.d}" fill="#000" stroke="#000" stroke-width="${gap * 2}" stroke-linejoin="round"/></g></mask>`);
  const letters = `<g mask="url(#${id}-kp)"><path transform="${pt}" d="${P.d}"/></g><g mask="url(#${id}-ks)"><path transform="${st}" d="${S.d}"/></g>`;
  let deco = '';
  if (ring === 'ring') deco = `<circle cx="${cx}" cy="${cy}" r="${ringR}" fill="none" stroke="${color}" stroke-width="${ringW}"/>`;
  if (ring === 'laurel') deco = laurel({ cx, cy, r: ringR, ringW, color });
  return { box, cx, cy, r: ringR, defs: parts.join(''), body: `<g fill="${color}">${deco}${letters}</g>` };
}

// Ring on the right, laurel branch on the left (from bottom, sweeping up).
export function laurel({ cx, cy, r, ringW, color, from = 115, to = 245, leaves = 9 }) {
  const rad = (a) => (a * Math.PI) / 180;
  const P = (a, rr = r) => [cx + rr * Math.cos(rad(a)), cy + rr * Math.sin(rad(a))];
  const f = (n) => n.toFixed(2);
  // Open ring: the arc not covered by the laurel (screen angles, y down; 90 = bottom, 180 = left)
  const a0 = to + 15, a1 = from - 9 + 360;
  const [x0, y0] = P(a0), [x1, y1] = P(a1);
  let out = `<path d="M${f(x0)} ${f(y0)} A${r} ${r} 0 1 1 ${f(x1)} ${f(y1)}" fill="none" stroke="${color}" stroke-width="${ringW}" stroke-linecap="round"/>`;
  // Stem along the left arc
  const [s0x, s0y] = P(from), [s1x, s1y] = P(to);
  out += `<path d="M${f(s0x)} ${f(s0y)} A${r} ${r} 0 0 1 ${f(s1x)} ${f(s1y)}" fill="none" stroke="${color}" stroke-width="${ringW * 0.9}" stroke-linecap="round"/>`;
  // Leaf pairs, tapering towards the tip (top)
  for (let i = 0; i < leaves; i++) {
    const t = i / (leaves - 1);
    const a = from + (to - from) * (0.04 + t * 0.92);
    const len = 46 - t * 20, wid = len * 0.36;
    const [bx, by] = P(a);
    const tangent = a + 90; // direction of growth along the arc (towards `to`)
    for (const side of [-1, 1]) {
      const dir = tangent + side * 38; // splay outwards / inwards
      const tip = [bx + len * Math.cos(rad(dir)), by + len * Math.sin(rad(dir))];
      const nx = Math.cos(rad(dir + 90)) * wid, ny = Math.sin(rad(dir + 90)) * wid;
      const m = [bx + (tip[0] - bx) * 0.5, by + (tip[1] - by) * 0.5];
      out += `<path d="M${f(bx)} ${f(by)} Q${f(m[0] + nx)} ${f(m[1] + ny)} ${f(tip[0])} ${f(tip[1])} Q${f(m[0] - nx)} ${f(m[1] - ny)} ${f(bx)} ${f(by)}Z"/>`;
    }
  }
  // Terminal bud at the tip
  const tipA = to + 2, [tx, ty] = P(tipA), dir = tipA + 90, len = 30;
  const tt = [tx + len * Math.cos(rad(dir)), ty + len * Math.sin(rad(dir))];
  const nx = Math.cos(rad(dir + 90)) * 9, ny = Math.sin(rad(dir + 90)) * 9, m = [(tx + tt[0]) / 2, (ty + tt[1]) / 2];
  out += `<path d="M${f(tx)} ${f(ty)} Q${f(m[0] + nx)} ${f(m[1] + ny)} ${f(tt[0])} ${f(tt[1])} Q${f(m[0] - nx)} ${f(m[1] - ny)} ${f(tx)} ${f(ty)}Z"/>`;
  return out;
}
