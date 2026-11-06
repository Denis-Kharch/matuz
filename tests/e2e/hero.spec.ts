import { test, expect } from '@playwright/test';
import { gotoClean } from './helpers';

test('hero fills the first screen and keeps focal point', async ({ page }) => {
    await gotoClean(page);
    const img = page.locator('[data-hero-img] img');
    await expect(img).toBeVisible();
    expect(await img.evaluate((el) => getComputedStyle(el).objectPosition)).toBe('24% 30%');
    const box = await page.locator('#top').boundingBox();
    const vh = page.viewportSize()!.height;
    expect(box!.height).toBeGreaterThanOrEqual(vh - 1);
});

test('hero KP button is inside the first screen', async ({ page }) => {
    await gotoClean(page);
    const btn = page.locator('[data-kp-source="hero"]');
    await expect(btn).toBeInViewport({ ratio: 1 });
});

test('marquee stops with reduced motion', async ({ browser }) => {
    const ctx = await browser.newContext({ reducedMotion: 'reduce' });
    const page = await ctx.newPage();
    await gotoClean(page);
    const name = await page.locator('[data-marquee-track]').evaluate((el) => getComputedStyle(el).animationName);
    expect(name).toBe('none');
    await ctx.close();
});

test('marquee animates by default', async ({ page }) => {
    await gotoClean(page);
    const name = await page.locator('[data-marquee-track]').evaluate((el) => getComputedStyle(el).animationName);
    expect(name).not.toBe('none');
});
