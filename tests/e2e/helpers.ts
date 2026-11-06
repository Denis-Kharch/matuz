import type { Page } from '@playwright/test';

/** Открыть страницу с уже сделанным выбором cookie (окно не мешает остальным тестам). */
export async function gotoClean(page: Page, path = '/') {
    await page.addInitScript(() => {
        try { localStorage.setItem('am_cookie_choice', 'accepted'); } catch {}
    });
    await page.goto(path);
}
