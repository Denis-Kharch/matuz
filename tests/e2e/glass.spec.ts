import { test, expect } from '@playwright/test';
import { gotoClean } from './helpers';

test('header bar is frosted glass', async ({ page }) => {
    await gotoClean(page);
    const filter = await page.locator('[data-header-bar]').evaluate((el) => getComputedStyle(el).backdropFilter);
    expect(filter).toContain('blur');
});

test('touch devices get blur without heavy refraction', async ({ page, isMobile }) => {
    test.skip(!isMobile);
    await gotoClean(page);
    await expect(page.locator('html')).not.toHaveClass(/glass-refract/);
    const filter = await page.locator('[data-header-bar]').evaluate((el) => getComputedStyle(el).backdropFilter);
    expect(filter).toContain('blur');
    expect(filter).not.toContain('url(');
});

test('desktop chromium gets refraction, glass keeps blur', async ({ page, browserName, isMobile }) => {
    test.skip(browserName !== 'chromium' || isMobile);
    await gotoClean(page);
    await expect(page.locator('html')).toHaveClass(/glass-refract/);
    const filter = await page.locator('[data-header-bar]').evaluate((el) => getComputedStyle(el).backdropFilter);
    expect(filter).toContain('url(');
    expect(filter).toContain('blur');
});

test('reduced transparency falls back to solid surfaces', async ({ browser }) => {
    const ctx = await browser.newContext({ reducedMotion: 'reduce' });
    const page = await ctx.newPage();
    await page.emulateMedia({ reducedMotion: 'reduce' });
    await page.addInitScript(() => localStorage.setItem('am_cookie_choice', 'accepted'));
    await page.goto('/');
    // prefers-reduced-transparency не эмулируется Playwright — проверяем хотя бы, что класс-фолбэк работает
    await page.evaluate(() => document.documentElement.classList.add('no-glass'));
    const filter = await page.locator('[data-header-bar]').evaluate((el) => getComputedStyle(el).backdropFilter);
    expect(filter).toBe('none');
    await ctx.close();
});
