import { defineConfig, devices } from '@playwright/test';

export default defineConfig({
    testDir: 'tests/e2e',
    fullyParallel: true,
    reporter: [['list']],
    use: { baseURL: 'http://127.0.0.1:4710', trace: 'retain-on-failure' },
    projects: [
        { name: 'desktop', use: { ...devices['Desktop Chrome'], viewport: { width: 1280, height: 800 } } },
        { name: 'mobile', use: { ...devices['Desktop Chrome'], viewport: { width: 375, height: 812 }, isMobile: true, hasTouch: true, deviceScaleFactor: 2 } },
    ],
    webServer: {
        command: 'php artisan serve --host=127.0.0.1 --port=4710',
        url: 'http://127.0.0.1:4710',
        reuseExistingServer: true,
        timeout: 60_000,
    },
});
