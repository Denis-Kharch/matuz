import { test, expect } from '@playwright/test';
import { gotoClean } from './helpers';

test('blocks below the hero fade in on scroll and end fully visible without leftovers', async ({ page }) => {
    await gotoClean(page, '/');
    const faqHeader = page.locator('#faq > .wrap > *').first();
    // До прокрутки блок спрятан анимацией
    await expect(faqHeader).toHaveClass(/reveal/);
    await faqHeader.scrollIntoViewIfNeeded();
    await expect(faqHeader).toHaveCSS('opacity', '1');
    // После появления служебные классы снимаются (не мешают эффектам наведения)
    await expect(faqHeader).not.toHaveClass(/reveal/, { timeout: 4000 });
    // Первый экран не участвует
    await expect(page.locator('#top .reveal')).toHaveCount(0);
});

test('reduced motion shows everything at once', async ({ browser }) => {
    const ctx = await browser.newContext({ reducedMotion: 'reduce' });
    const page = await ctx.newPage();
    await gotoClean(page, '/');
    await expect(page.locator('.reveal')).toHaveCount(0);
    await ctx.close();
});
