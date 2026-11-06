import { test, expect, type Page } from '@playwright/test';

// Шпион на document.startViewTransition: считаем вызовы и запоминаем имена переходов в момент старта.
async function setup(page: Page) {
    await page.addInitScript(() => {
        try {
            localStorage.setItem('am_cookie_choice', 'accepted');
        } catch {}
        (window as any).__vt = 0;
        (window as any).__vtFailed = 0;
        (window as any).__vtOk = 0;
        const orig = document.startViewTransition?.bind(document);
        if (orig) {
            (document as any).startViewTransition = (cb: any) => {
                (window as any).__vt++;
                const t = orig(cb);
                t.ready.then(() => (window as any).__vtOk++, () => (window as any).__vtFailed++);
                return t;
            };
        }
    });
}

test('case card morphs into the fullscreen case and back', async ({ page }) => {
    await setup(page);
    await page.goto('/');
    const card = page.locator('[data-case-card]').first();
    await card.scrollIntoViewIfNeeded();
    await card.click();
    const overlay = page.locator('[data-modal^="case-"]:visible');
    await expect(overlay).toHaveCount(1);
    await expect(overlay.locator('[data-case-banner]')).toBeVisible();
    await expect(page).toHaveURL(/\/cases\//);
    expect(await page.evaluate(() => (window as any).__vt)).toBe(1);

    await page.keyboard.press('Escape');
    await expect(page.locator('[data-modal^="case-"]:visible')).toHaveCount(0);
    await expect(page).not.toHaveURL(/\/cases\//);
    expect(await page.evaluate(() => (window as any).__vt)).toBe(2);
    await expect.poll(() => page.evaluate(() => (window as any).__vtOk + (window as any).__vtFailed)).toBe(await page.evaluate(() => (window as any).__vt));
    expect(await page.evaluate(() => (window as any).__vtFailed), 'переход прерван браузером').toBe(0);
    await expect(card).toBeFocused();
    // после перехода имена очищены — иначе следующий переход сломается
    // (имена убираются по окончании анимации перехода)
    await expect
        .poll(() => page.evaluate(() => [...document.querySelectorAll('[data-case-thumb],[data-case-banner]')].map((e) => (e as HTMLElement).style.viewTransitionName).filter(Boolean)))
        .toEqual([]);
});

test('opening a second case after the first still morphs', async ({ page }) => {
    await setup(page);
    await page.goto('/');
    for (const i of [0, 3]) {
        const card = page.locator('[data-case-card]').nth(i);
        await card.scrollIntoViewIfNeeded();
        await card.click();
        await expect(page.locator('[data-modal^="case-"]:visible')).toHaveCount(1);
        await page.keyboard.press('Escape');
        await expect(page.locator('[data-modal^="case-"]:visible')).toHaveCount(0);
    }
    expect(await page.evaluate(() => (window as any).__vt)).toBe(4);
    await expect.poll(() => page.evaluate(() => (window as any).__vtOk + (window as any).__vtFailed)).toBe(await page.evaluate(() => (window as any).__vt));
    expect(await page.evaluate(() => (window as any).__vtFailed), 'переход прерван браузером').toBe(0);
});

test('reduced motion opens cases without view transitions', async ({ browser }) => {
    const ctx = await browser.newContext({ reducedMotion: 'reduce' });
    const page = await ctx.newPage();
    await setup(page);
    await page.goto('/');
    const card = page.locator('[data-case-card]').first();
    await card.scrollIntoViewIfNeeded();
    await card.click();
    await expect(page.locator('[data-modal^="case-"]:visible')).toHaveCount(1);
    await page.keyboard.press('Escape');
    await expect(page.locator('[data-modal^="case-"]:visible')).toHaveCount(0);
    expect(await page.evaluate(() => (window as any).__vt)).toBe(0);
    await ctx.close();
});

test('browser Back from a morphed case closes it', async ({ page }) => {
    await setup(page);
    await page.goto('/');
    const card = page.locator('[data-case-card]').nth(2);
    await card.scrollIntoViewIfNeeded();
    await card.click();
    await expect(page).toHaveURL(/\/cases\//);
    await page.goBack();
    await expect(page.locator('[data-modal^="case-"]:visible')).toHaveCount(0);
    await expect(page).not.toHaveURL(/\/cases\//);
});
