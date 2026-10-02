import * as hb from 'harfbuzzjs';
import { readFileSync } from 'node:fs';

const F = 'node_modules/@expo-google-fonts';
export const FONTS = {
  cinzel: `${F}/cinzel/500Medium/Cinzel_500Medium.ttf`,
  cormBold: `${F}/cormorant-garamond/700Bold/CormorantGaramond_700Bold.ttf`,
  cormBoldIt: `${F}/cormorant-garamond/700Bold_Italic/CormorantGaramond_700Bold_Italic.ttf`,
  amiri: `${F}/amiri/700Bold/Amiri_700Bold.ttf`,
};

const cache = new Map();
function load(key) {
  if (!cache.has(key)) {
    const data = readFileSync(FONTS[key]);
    const face = new hb.Face(new hb.Blob(data.buffer.slice(data.byteOffset, data.byteOffset + data.byteLength)));
    const font = new hb.Font(face);
    cache.set(key, { face, font, upem: face.upem ?? face.getUpem?.() ?? 1000 });
  }
  return cache.get(key);
}

const num = (n) => +n.toFixed(2);

// Transform a font-space path (y up) into SVG space: x' = ox + x*s, y' = oy - y*s
function transformPath(d, s, ox, oy) {
  return d.replace(/([MLQCZ])([^MLQCZ]*)/g, (_, cmd, args) => {
    const nums = args.trim().length ? args.trim().split(/[\s,]+/).map(Number) : [];
    const out = [];
    for (let i = 0; i < nums.length; i += 2) out.push(num(ox + nums[i] * s), num(oy - nums[i + 1] * s));
    return cmd + out.join(' ');
  });
}

/**
 * Shape text and return an SVG path positioned with its baseline at y=0, starting at x=0.
 * tracking is extra space per glyph in em units.
 */
export function textPath(fontKey, text, size, { tracking = 0, features = '' } = {}) {
  const { font, upem } = load(fontKey);
  const s = size / upem;
  const buf = new hb.Buffer();
  buf.addText(text);
  buf.guessSegmentProperties();
  hb.shape(font, buf, features || undefined);
  const infos = buf.getGlyphInfos();
  const pos = buf.getGlyphPositions();
  let pen = 0;
  const parts = [];
  // RTL buffers come out in visual order already
  infos.forEach((g, i) => {
    const p = pos[i];
    const d = font.glyphToPath(g.codepoint);
    if (d) parts.push(transformPath(d, s, (pen + p.xOffset) * s, -p.yOffset * s));
    pen += p.xAdvance + (i < infos.length - 1 ? tracking * upem : 0);
  });
  buf.destroy?.();
  return { d: parts.join(''), width: pen * s };
}

export function glyphMetrics(fontKey) {
  const { face, upem } = load(fontKey);
  return { upem, face };
}

export function bbox(d) {
  const n = d.match(/-?\d+(\.\d+)?/g).map(Number);
  let x0 = Infinity, y0 = Infinity, x1 = -Infinity, y1 = -Infinity;
  for (let i = 0; i < n.length; i += 2) {
    x0 = Math.min(x0, n[i]); x1 = Math.max(x1, n[i]);
    y0 = Math.min(y0, n[i + 1]); y1 = Math.max(y1, n[i + 1]);
  }
  return { x0, y0, x1, y1, w: x1 - x0, h: y1 - y0 };
}
