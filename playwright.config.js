// Browser tests (npm run test:e2e) against a running local site with the sample catalogue
// (php artisan db:seed --class=DemoCatalogSeeder) and the sandbox payment page enabled.
// Uses the installed Microsoft Edge, so no browser download is needed.
import { defineConfig } from '@playwright/test';

export default defineConfig({
    testDir: 'tests/e2e',
    outputDir: 'storage/framework/testing/e2e',
    fullyParallel: false,
    workers: 1, // the local PHP server handles one request at a time
    timeout: 60_000,
    expect: { timeout: 10_000 },
    reporter: [['list']],
    use: {
        baseURL: process.env.E2E_BASE_URL ?? 'http://localhost:8000',
        channel: 'msedge',
        headless: true,
        locale: 'ar-SA',
        trace: 'off',
        screenshot: 'only-on-failure',
    },
});
