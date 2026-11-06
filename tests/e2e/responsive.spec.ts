import { test, expect } from '@playwright/test';

const widths = [375, 768, 1280, 1440];

// каждый тест открывает несколько страниц — под нагрузкой полного прогона нужно больше времени
test.describe.configure({ timeout: 90_000 });

for (const w of widths) {
    test(`no horizontal scroll and KP button not covered at ${w}px`, async ({ browser }) => {
        const ctx = await browser.newContext({ viewport: { width: w, height: 900 } });
        const page = await ctx.newPage();
        await page.addInitScript(() => localStorage.setItem('am_cookie_choice', 'accepted'));
        for (const path of ['/', '/events']) {
            await page.goto(path, { waitUntil: 'networkidle' });
            expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth), `${path} @${w}`).toBe(true);
        }
        await page.goto('/', { waitUntil: 'networkidle' });
        await page.evaluate(() => window.scrollTo(0, document.body.scrollHeight / 2));
        await page.locator('[data-kp-source="floating"]').click();
        const submit = page.getByRole('button', { name: 'Сформировать КП' });
        await expect(submit).toBeVisible();
        const floating = page.locator('[data-kp-source="floating"]');
        await expect(floating).toBeHidden();
        await page.screenshot({ path: `test-results/responsive-${w}.png`, fullPage: false });
        await ctx.close();
    });
}
