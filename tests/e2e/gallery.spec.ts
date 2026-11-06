import { test, expect } from '@playwright/test';
import { gotoClean } from './helpers';

test('lightbox: arrows wrap, counter updates, Esc closes', async ({ page }) => {
    await gotoClean(page, '/#gallery');
    const items = page.locator('[data-gallery-item]');
    const n = await items.count();
    await items.last().scrollIntoViewIfNeeded();
    await items.last().click();
    const box = page.getByRole('dialog', { name: 'Галерея' });
    await expect(box).toHaveAttribute('data-index', String(n - 1));
    await expect(box).toContainText(`${n} / ${n}`);
    await page.keyboard.press('ArrowRight');
    await expect(box).toHaveAttribute('data-index', '0');
    await page.keyboard.press('ArrowLeft');
    await expect(box).toHaveAttribute('data-index', String(n - 1));
    await page.keyboard.press('Escape');
    await expect(box).toBeHidden();
});

test('lightbox swipe changes photo on touch', async ({ page, isMobile }) => {
    test.skip(!isMobile);
    await gotoClean(page, '/#gallery');
    await page.locator('[data-gallery-item]').first().click();
    const box = page.getByRole('dialog', { name: 'Галерея' });
    await expect(box).toHaveAttribute('data-index', '0');
    const stage = box.locator('[data-lightbox-stage]');
    const b = (await stage.boundingBox())!;
    await stage.dispatchEvent('pointerdown', { clientX: b.x + b.width * 0.8, clientY: b.y + 50, pointerType: 'touch', isPrimary: true });
    await stage.dispatchEvent('pointerup', { clientX: b.x + b.width * 0.2, clientY: b.y + 50, pointerType: 'touch', isPrimary: true });
    await expect(box).toHaveAttribute('data-index', '1');
});

test('faq item toggles', async ({ page }) => {
    await gotoClean(page, '/#faq');
    const q = page.getByRole('button', { name: /Как заказать обучение для компании/ });
    await q.click();
    await expect(q).toHaveAttribute('aria-expanded', 'true');
    await expect(page.getByText('сразу получите структуру, форматы и цены')).toBeVisible();
});
