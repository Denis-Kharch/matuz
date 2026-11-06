import { test, expect } from '@playwright/test';
import { gotoClean } from './helpers';

test('format card opens detail and Esc returns focus', async ({ page }) => {
    await gotoClean(page, '/#directions');
    const card = page.locator('[data-format-card]').first();
    await card.click();
    const dialog = page.getByRole('dialog', { name: 'Формат обучения' });
    await expect(dialog).toBeVisible();
    await expect(dialog).toContainText('Индивидуальное обучение');
    await expect(dialog).toContainText('Формат для первых лиц');
    await page.keyboard.press('Escape');
    await expect(dialog).toBeHidden();
    await expect(card).toBeFocused();
});

test('format dialog shows the clicked format', async ({ page }) => {
    await gotoClean(page, '/#directions');
    await page.locator('[data-format-card]').nth(7).click();
    await expect(page.getByRole('dialog', { name: 'Формат обучения' })).toContainText('Вебинар');
});

test('topic group expands and collapses with aria-expanded', async ({ page }) => {
    await gotoClean(page, '/#directions');
    const h = page.getByRole('button', { name: /Обучение продажам/ });
    await expect(h).toHaveAttribute('aria-expanded', 'false');
    await h.click();
    await expect(h).toHaveAttribute('aria-expanded', 'true');
    await expect(page.getByText('Базовые продажи для начинающих')).toBeVisible();
    await h.click();
    await expect(h).toHaveAttribute('aria-expanded', 'false');
    await expect(page.getByText('Базовые продажи для начинающих')).toBeHidden();
});

test('event services: only one open at a time', async ({ page }) => {
    await gotoClean(page, '/#directions');
    const a = page.getByRole('button', { name: /Организация форума$/ });
    const b = page.getByRole('button', { name: /Спикерство$/ });
    await a.click();
    await expect(a).toHaveAttribute('aria-expanded', 'true');
    await b.click();
    await expect(b).toHaveAttribute('aria-expanded', 'true');
    await expect(a).toHaveAttribute('aria-expanded', 'false');
});
