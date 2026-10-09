// The customer journey end to end: browse → search → wishlist → cart → checkout → sandbox payment → order page.
import { expect, test } from '@playwright/test';

/** Opens the first piece in the store that can still be bought. */
async function openBuyablePiece(page) {
    await page.goto('/en/store?available=1');
    const links = await page.locator('article a[href*="/en/product/"]').evaluateAll(as => [...new Set(as.map(a => a.getAttribute('href')))]);
    for (const href of links) {
        await page.goto(href);
        if (await page.getByRole('button', { name: 'Add to cart' }).first().isVisible()) return href;
    }
    throw new Error('No piece in stock: seed the sample catalogue first.');
}

/** Pickup is the default way to receive a piece; the customer picks one of the showrooms. */
async function choosePickupShowroom(page) {
    const showroom = page.locator('select[name=pickup_branch]');
    if (await showroom.isVisible()) await showroom.selectOption({ index: 1 });
}

test('a guest finds a piece, saves it, buys it and pays', async ({ page }) => {
    // Search (Arabic and English share one index).
    await page.goto('/en');
    await page.getByRole('searchbox').first().fill('chandelier');
    await page.keyboard.press('Enter');
    await expect(page).toHaveURL(/\/en\/search\?q=chandelier/);
    await expect(page.locator('article').first()).toBeVisible();

    const productUrl = await openBuyablePiece(page);
    const name = (await page.locator('h1').innerText()).trim();

    // Wishlist, then cart: both update the header counters without a page reload.
    await page.getByRole('button', { name: 'Save to wishlist' }).first().click();
    await page.goto('/en/wishlist');
    await expect(page.getByText(name).first()).toBeVisible();

    await page.goto(productUrl);
    await page.getByRole('button', { name: 'Add to cart' }).first().click();
    await expect(page.getByRole('status').filter({ hasText: /cart/i }).first()).toBeVisible();

    await page.goto('/en/cart');
    await expect(page.getByText(name).first()).toBeVisible();
    await page.getByRole('link', { name: 'Checkout' }).click();

    // Checkout as a guest with test details; pickup / payment keep their defaults.
    await page.locator('input[name=name]').fill('E2E Test Customer');
    await page.locator('input[name=phone]').fill('0500000000');
    await page.locator('input[name=email]').fill('e2e@example.test');
    await choosePickupShowroom(page);
    await page.getByRole('button', { name: 'Continue to payment' }).click();

    // Internal test payment page (sandbox only).
    await page.locator('button[name=outcome][value=approve]').click();

    await expect(page).toHaveURL(/\/en\/orders\/PS-\d{4}-\d{6}/);
    await expect(page.getByText('Thank you! Your order is confirmed')).toBeVisible();
    await expect(page.getByText(name).first()).toBeVisible();
});

test('a declined payment leaves the order unpaid and offers to try again', async ({ page }) => {
    await openBuyablePiece(page);
    await page.getByRole('button', { name: 'Add to cart' }).first().click();
    await expect(page.getByRole('status').filter({ hasText: /cart/i }).first()).toBeVisible();
    await page.goto('/en/checkout');
    await page.locator('input[name=name]').fill('E2E Declined');
    await page.locator('input[name=phone]').fill('0500000001');
    await choosePickupShowroom(page);
    await page.getByRole('button', { name: 'Continue to payment' }).click();
    await page.locator('button[name=outcome][value=decline]').click();

    await expect(page).toHaveURL(/\/en\/orders\/PS-/);
    await expect(page.getByRole('button', { name: /pay/i }).first()).toBeVisible();
});

test('the Arabic store works right to left on a phone', async ({ page }) => {
    await page.setViewportSize({ width: 390, height: 844 });
    await page.goto('/');
    await expect(page.locator('html')).toHaveAttribute('dir', 'rtl');

    await page.getByRole('button', { name: 'فتح القائمة' }).click();
    const menu = page.getByRole('dialog', { name: 'القائمة الرئيسية' });
    await expect(menu).toBeVisible();
    // The panel must cover the screen, not be squeezed into the header (regression: header backdrop-filter).
    const box = await menu.boundingBox();
    expect(box?.height ?? 0).toBeGreaterThan(800);
    await menu.getByRole('link', { name: 'المتجر' }).click();
    await expect(page).toHaveURL(/\/store/);
    await expect(page.locator('h1')).toHaveText('المتجر');
});
