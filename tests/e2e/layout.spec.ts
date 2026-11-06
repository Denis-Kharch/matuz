import { test, expect } from '@playwright/test';
import { gotoClean } from './helpers';

test('mobile menu opens and closes on link click', async ({ page, isMobile }) => {
    test.skip(!isMobile);
    await gotoClean(page);
    await page.getByRole('button', { name: 'Меню' }).click();
    const menu = page.getByRole('navigation', { name: 'Меню сайта' });
    await expect(menu).toBeVisible();
    await menu.getByRole('link', { name: 'Кейсы' }).click();
    await expect(menu).toBeHidden();
});

test('desktop nav shows all sections', async ({ page, isMobile }) => {
    test.skip(isMobile);
    await gotoClean(page);
    const nav = page.getByRole('navigation', { name: 'Разделы' });
    for (const label of ['Об Андрее', 'Направления', 'Кейсы', 'События', 'Партнёры', 'Вопросы', 'Галерея', 'Регалии', 'Контакты']) {
        await expect(nav.getByRole('link', { name: label })).toBeVisible();
    }
});

test('cookie choice persists', async ({ page }) => {
    await page.goto('/');
    await page.getByRole('button', { name: 'Принять' }).click();
    await page.reload();
    await page.waitForTimeout(1500);
    await expect(page.getByRole('button', { name: 'Принять' })).toBeHidden();
});

test('cookie banner cannot be dismissed by Escape', async ({ page }) => {
    await page.goto('/');
    await expect(page.getByRole('button', { name: 'Принять' })).toBeVisible();
    await page.keyboard.press('Escape');
    await expect(page.getByRole('button', { name: 'Принять' })).toBeVisible();
});

test('floating KP hidden while any modal open, visible otherwise', async ({ page }) => {
    await page.goto('/');
    await expect(page.getByRole('button', { name: 'Принять' })).toBeVisible();
    await expect(page.locator('[data-kp-source="floating"]')).toBeHidden();
    await page.getByRole('button', { name: 'Принять' }).click();
    await expect(page.locator('[data-kp-source="floating"]')).toBeVisible();
});

test('no horizontal scroll', async ({ page }) => {
    await gotoClean(page);
    expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBe(true);
});
