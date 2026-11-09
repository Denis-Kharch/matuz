import { defineConfig, devices } from '@playwright/test';

// Тесты админки (admin-*.spec.ts) идут против отдельного сервера: scripts/e2e-admin-server.mjs
// поднимает чистую базу, storage и папку сайта в storage/e2e-admin (CONTENT_SOURCE=database).
const ADMIN_URL = 'http://127.0.0.1:4730';

export default defineConfig({
    testDir: 'tests/e2e',
    fullyParallel: true,
    reporter: [['list']],
    use: { baseURL: 'http://127.0.0.1:4710', trace: 'retain-on-failure' },
    projects: [
        { name: 'desktop', testIgnore: /admin-.*\.spec\.ts/, use: { ...devices['Desktop Chrome'], viewport: { width: 1280, height: 800 } } },
        { name: 'mobile', testIgnore: /admin-.*\.spec\.ts/, use: { ...devices['Desktop Chrome'], viewport: { width: 375, height: 812 }, isMobile: true, hasTouch: true, deviceScaleFactor: 2 } },
        { name: 'admin', testMatch: /admin-.*\.spec\.ts/, use: { ...devices['Desktop Chrome'], viewport: { width: 1280, height: 900 }, baseURL: ADMIN_URL } },
    ],
    webServer: [
        {
            command: 'php artisan serve --host=127.0.0.1 --port=4710',
            url: 'http://127.0.0.1:4710',
            reuseExistingServer: true,
            timeout: 60_000,
        },
        {
            command: 'node scripts/e2e-admin-server.mjs',
            url: `${ADMIN_URL}/admin/login`,
            reuseExistingServer: false,
            timeout: 180_000,
        },
    ],
});
