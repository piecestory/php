// Staff panel in a real browser: sign in, find the latest order, open it. Credentials come from
// E2E_ADMIN_EMAIL / E2E_ADMIN_PASSWORD or the local, git-ignored .dev/qa-admin.txt (email=…, password=…).
import { expect, test } from '@playwright/test';
import fs from 'node:fs';

function credentials() {
    if (process.env.E2E_ADMIN_EMAIL && process.env.E2E_ADMIN_PASSWORD) {
        return { email: process.env.E2E_ADMIN_EMAIL, password: process.env.E2E_ADMIN_PASSWORD };
    }
    const file = '.dev/qa-admin.txt';
    if (!fs.existsSync(file)) return null;
    return Object.fromEntries(fs.readFileSync(file, 'utf8').trim().split(/\r?\n/).map(line => line.split('=').map(s => s.trim())));
}

test('staff sign in and open an order', async ({ page }) => {
    const creds = credentials();
    test.skip(!creds, 'No staff test account configured.');

    await page.goto('/admin/login');
    await page.locator('input[type=email]').fill(creds.email);
    await page.locator('input[type=password]').fill(creds.password);
    await page.locator('form button[type=submit]').click();
    await expect(page).toHaveURL(/\/admin\/?$/);

    const violations = [];
    page.on('console', msg => { if (/Content Security Policy/i.test(msg.text())) violations.push(msg.text()); });

    await page.goto('/admin/orders');
    const firstOrder = page.locator('table tbody tr').first();
    await expect(firstOrder).toBeVisible();
    await firstOrder.click();
    await expect(page).toHaveURL(/\/admin\/orders\/\d+/);
    await expect(page.locator('h1')).toContainText('PS-');

    expect(violations).toEqual([]);
});

test('customers cannot open the staff panel', async ({ page }) => {
    const response = await page.goto('/admin');
    await expect(page).toHaveURL(/\/admin\/login/);
    expect(response?.status()).toBeLessThan(400);
});
