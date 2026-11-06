import { test, expect } from '@playwright/test';
import { gotoClean } from './helpers';

test('event from home opens overlay with its URL; Back closes', async ({ page }) => {
    await gotoClean(page, '/');
    const item = page.locator('[data-event-item] a').first();
    await item.scrollIntoViewIfNeeded();
    await item.click();
    await expect(page).toHaveURL(/\/events\/[a-z0-9-]+$/);
    await expect(page.locator('[data-modal^="event-"]:visible')).toHaveCount(1);
    await page.goBack();
    await expect(page.locator('[data-modal^="event-"]:visible')).toHaveCount(0);
    await expect(page).not.toHaveURL(/\/events\//);
});

test('direct event link on events page; Esc returns to /events', async ({ page }) => {
    await gotoClean(page, '/events');
    const href = await page.locator('[data-event-item] a').nth(3).getAttribute('href');
    await page.goto(href!);
    await expect(page.locator('[data-modal^="event-"]:visible')).toHaveCount(1);
    await page.keyboard.press('Escape');
    await expect(page.locator('[data-modal^="event-"]:visible')).toHaveCount(0);
    await expect(page).toHaveURL(/\/events$/);
});

test('header anchors on events page lead to home sections', async ({ page, isMobile }) => {
    test.skip(isMobile);
    await gotoClean(page, '/events');
    const href = await page.getByRole('navigation', { name: 'Разделы' }).getByRole('link', { name: 'Кейсы' }).getAttribute('href');
    expect(href).toMatch(/\/#cases$/);
});
