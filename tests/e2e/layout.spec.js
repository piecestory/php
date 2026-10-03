// Every main page at every target width, in Arabic (RTL) and English (LTR): nothing may overflow sideways.
import { expect, test } from '@playwright/test';

const WIDTHS = [320, 375, 390, 430, 768, 1024, 1280, 1440, 1920];
const PAGES = ['', 'store', 'collections', 'cart', 'checkout', 'contact', 'faq', 'track-order', 'login', 'register', 'personal-finder', 'sell-with-us'];

for (const locale of ['ar', 'en']) {
    const prefix = locale === 'en' ? '/en/' : '/';

    test(`no sideways scrolling at any width (${locale})`, async ({ page }) => {
        test.setTimeout(15 * 60_000);

        // A product page too: its address is in this language (slugs differ per language).
        await page.goto(prefix + 'store');
        const product = await page.locator('article a[href*="/product/"]').first().getAttribute('href');
        const urls = [...PAGES.map(p => prefix + p), ...(product ? [new URL(product).pathname] : [])];
        const problems = [];

        for (const width of WIDTHS) {
            await page.setViewportSize({ width, height: 900 });
            for (const url of urls) {
                const response = await page.goto(url, { waitUntil: 'domcontentloaded' });
                expect(response?.status(), url).toBeLessThan(400);
                await expect(page.locator('html')).toHaveAttribute('dir', locale === 'ar' ? 'rtl' : 'ltr');

                const overflow = await page.evaluate(() => {
                    const doc = document.documentElement;
                    if (doc.scrollWidth <= window.innerWidth + 1) return null;
                    // Name the element sticking out furthest, to make the failure actionable.
                    let worst = null;
                    for (const el of document.body.querySelectorAll('*')) {
                        const r = el.getBoundingClientRect();
                        const out = Math.max(r.right - window.innerWidth, -r.left);
                        if (out > 1 && (!worst || out > worst.out)) worst = { out: Math.round(out), el: el.tagName.toLowerCase() + '.' + String(el.className).split(' ').slice(0, 3).join('.') };
                    }
                    return { scrollWidth: doc.scrollWidth, worst };
                });
                if (overflow) problems.push(`${width}px ${decodeURI(url)}: page is ${overflow.scrollWidth}px wide; ${JSON.stringify(overflow.worst)}`);
            }
        }

        expect(problems, problems.join('\n')).toEqual([]);
    });
}
