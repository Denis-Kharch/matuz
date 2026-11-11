import { test, expect } from '@playwright/test';
import { gotoClean } from './helpers';

test('cases strip: arrows scroll, mouse drag scrolls without opening a case, filter returns to start', async ({ page, isMobile }) => {
    test.skip(isMobile, 'перетаскивание мышью и стрелки — на компьютере');
    await gotoClean(page, '/#cases');
    const track = page.locator('[data-cases-strip]');
    await track.scrollIntoViewIfNeeded();
    const left = () => track.evaluate((el) => el.scrollLeft);
    const back = page.getByRole('button', { name: 'Листать кейсы: назад' });
    await expect(back).toBeDisabled();

    await page.getByRole('button', { name: 'Листать кейсы: вперёд' }).click();
    await expect.poll(left).toBeGreaterThan(100);
    await expect(back).toBeEnabled();

    // Потянуть мышью влево — лента едет вперёд, кейс не открывается
    const before = await left();
    const box = (await track.boundingBox())!;
    await page.mouse.move(box.x + box.width * 0.7, box.y + box.height / 2);
    await page.mouse.down();
    await page.mouse.move(box.x + box.width * 0.4, box.y + box.height / 2, { steps: 8 });
    await page.mouse.up();
    await expect.poll(left).toBeGreaterThan(before + 50);
    await expect(page).not.toHaveURL(/\/cases\//);
    await expect(page.locator('[data-modal^="case-"]:visible')).toHaveCount(0);

    // Смена фильтра возвращает ленту в начало
    await page.getByRole('button', { name: 'Обучение', exact: true }).click();
    await expect.poll(left).toBeLessThan(5);
});
