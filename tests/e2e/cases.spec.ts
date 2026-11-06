import { test, expect } from '@playwright/test';
import { gotoClean } from './helpers';

test('case opens fullscreen, URL changes, Back closes', async ({ page }) => {
    await gotoClean(page, '/');
    const card = page.locator('[data-case-card]').first();
    await card.scrollIntoViewIfNeeded();
    await card.click();
    await expect(page).toHaveURL(/\/cases\//);
    const dialog = page.locator('[data-modal^="case-"]:visible');
    await expect(dialog).toHaveCount(1);
    await expect(dialog).toContainText('Запрос заказчика');
    await page.goBack();
    await expect(page.locator('[data-modal^="case-"]:visible')).toHaveCount(0);
    await expect(page).not.toHaveURL(/\/cases\//);
});

test('closing case with Esc returns to home URL and focus to card', async ({ page }) => {
    await gotoClean(page, '/');
    const card = page.locator('[data-case-card]').nth(1);
    await card.scrollIntoViewIfNeeded();
    await card.click();
    await expect(page).toHaveURL(/\/cases\//);
    await page.keyboard.press('Escape');
    await expect(page).not.toHaveURL(/\/cases\//);
    await expect(card).toBeFocused();
});

test('direct case link opens overlay; Esc goes to home URL', async ({ page }) => {
    await gotoClean(page, '/');
    const href = await page.locator('[data-case-card]').first().getAttribute('href');
    await page.goto(href!);
    await expect(page.locator('[data-modal^="case-"]:visible')).toHaveCount(1);
    await page.keyboard.press('Escape');
    await expect(page.locator('[data-modal^="case-"]:visible')).toHaveCount(0);
    await expect(page).not.toHaveURL(/\/cases\//);
});

test('direction filter shows only matching cases', async ({ page }) => {
    await gotoClean(page, '/#cases');
    await page.getByRole('button', { name: 'Мероприятия', exact: true }).click();
    const visible = page.locator('[data-case-card]:visible');
    await expect(visible).toHaveCount(3); // в данных 3 кейса-мероприятия; ждём окончания анимации скрытия
    for (let i = 0; i < 3; i++) await expect(visible.nth(i)).toHaveAttribute('data-direction', 'events');
});
