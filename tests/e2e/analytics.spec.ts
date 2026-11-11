import { test, expect } from '@playwright/test';

// Номер счётчика подставляем в страницу до запуска скриптов (на тестовом сервере его нет в настройках).
async function withCounter(page: import('@playwright/test').Page, choice: string | null) {
    await page.addInitScript((choice) => {
        try {
            if (choice) localStorage.setItem('am_cookie_choice', choice);
            else localStorage.removeItem('am_cookie_choice');
        } catch {}
        document.addEventListener('readystatechange', () => {
            if (document.readyState !== 'interactive' || document.querySelector('meta[name="ym-id"]')) return;
            const m = document.createElement('meta');
            m.name = 'ym-id';
            m.content = '12345';
            document.head.append(m);
        });
    }, choice);
    const hits: string[] = [];
    // В сеть не ходим: отвечаем пустым скриптом и считаем обращения.
    await page.route('https://mc.yandex.ru/**', (route) => {
        hits.push(route.request().url());
        route.fulfill({ status: 200, contentType: 'application/javascript', body: '' });
    });
    return hits;
}

test('metrika loads only after cookie consent', async ({ page }) => {
    const hits = await withCounter(page, null);
    await page.goto('/');
    await page.waitForTimeout(1500);
    expect(hits).toHaveLength(0);
    await page.getByRole('button', { name: 'Принять' }).click();
    await expect.poll(() => hits.length).toBeGreaterThan(0);
    expect(hits[0]).toContain('/metrika/tag.js');
});

test('metrika stays off after «Отказать и остаться» and loads at once when consent was given earlier', async ({ browser }) => {
    const declined = await browser.newPage();
    const off = await withCounter(declined, 'declined');
    await declined.goto('/');
    await declined.waitForTimeout(1500);
    expect(off).toHaveLength(0);
    await declined.close();

    const accepted = await browser.newPage();
    const on = await withCounter(accepted, 'accepted');
    await accepted.goto('/');
    await expect.poll(() => on.length).toBeGreaterThan(0);
    await accepted.close();
});
