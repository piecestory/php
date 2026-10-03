// WCAG 2.1 AA checks (axe-core) on the main pages in both languages. Serious and critical findings fail the test.
import AxeBuilder from '@axe-core/playwright';
import { expect, test } from '@playwright/test';

const PAGES = ['', 'store', 'collections', 'cart', 'contact', 'faq', 'track-order', 'login', 'register', 'personal-finder', 'sell-with-us'];

async function expectAccessible(page) {
    const { violations } = await new AxeBuilder({ page }).withTags(['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa']).analyze();
    const blocking = violations.filter(v => v.impact === 'serious' || v.impact === 'critical');
    const report = blocking.map(v => `${v.impact} ${v.id}: ${v.help}\n  ${v.nodes.slice(0, 3).map(n => n.target.join(' ')).join('\n  ')}`).join('\n');

    expect(blocking, report).toEqual([]);
}

for (const locale of ['ar', 'en']) {
    const prefix = locale === 'en' ? '/en/' : '/';

    for (const p of PAGES) {
        test(`accessible: ${prefix + p}`, async ({ page }) => {
            await page.goto(prefix + p);
            await expectAccessible(page);
        });
    }

    test(`accessible: product page (${locale})`, async ({ page }) => {
        await page.goto(prefix + 'store');
        await page.locator('article a[href*="/product/"]').first().click();
        await expect(page).toHaveURL(/\/product\//);
        await expectAccessible(page);
    });
}
