import { build, favicon } from './build.mjs';
import { Resvg } from '@resvg/resvg-js';
import { writeFileSync, mkdirSync } from 'node:fs';
const root = process.argv[2];
const KIT = `${root}/docs/brand/logo`, SITE = `${root}/resources/images/brand`, PUB = root + '/public';
for (const d of [KIT, KIT + '/png', SITE]) mkdirSync(d, { recursive: true });

const COLORS = { gold: '#9A7338', 'gold-light': '#C9A46A', ink: '#1C1612', white: '#FFFFFF' };
const png = (svgStr, width) => new Resvg(svgStr, { fitTo: { mode: 'width', value: width } }).render().asPng();

for (const [k, c] of Object.entries(COLORS)) {
  const set = build(c, 'ps' + k.replace('-', ''));
  for (const [n, s] of Object.entries(set)) {
    writeFileSync(`${KIT}/${n}-${k}.svg`, s);
    if (['stacked', 'emblem', 'horizontal-ar', 'horizontal-en'].includes(n)) writeFileSync(`${KIT}/png/${n}-${k}.png`, png(s, n === 'emblem' ? 1200 : 2400));
  }
}
// Site versions inherit the surrounding text colour
const site = build('currentColor', 'pslogo');
for (const n of ['emblem', 'horizontal-ar', 'horizontal-en', 'compact-ar']) writeFileSync(`${SITE}/${n}.svg`, site[n]);

// Favicons
const fav = favicon('#1C1612', '#C9A46A');
writeFileSync(`${PUB}/favicon.svg`, fav);
writeFileSync(`${KIT}/app-icon.svg`, fav);
writeFileSync(`${PUB}/apple-touch-icon.png`, png(fav, 180));
writeFileSync(`${KIT}/png/app-icon-512.png`, png(fav, 512));
// ICO containing PNG-encoded 16/32/48 images
const sizes = [16, 32, 48].map((w) => ({ w, data: png(fav, w) }));
const header = Buffer.alloc(6 + 16 * sizes.length);
header.writeUInt16LE(0, 0); header.writeUInt16LE(1, 2); header.writeUInt16LE(sizes.length, 4);
let offset = header.length;
sizes.forEach(({ w, data }, i) => {
  const o = 6 + 16 * i;
  header.writeUInt8(w, o); header.writeUInt8(w, o + 1); header.writeUInt8(0, o + 2); header.writeUInt8(0, o + 3);
  header.writeUInt16LE(1, o + 4); header.writeUInt16LE(32, o + 6);
  header.writeUInt32LE(data.length, o + 8); header.writeUInt32LE(offset, o + 12);
  offset += data.length;
});
writeFileSync(`${PUB}/favicon.ico`, Buffer.concat([header, ...sizes.map((s) => s.data)]));
console.log('exported');
