import { test, expect, type Page } from '@playwright/test';
import { gotoClean } from './helpers';

const doc = (page: Page) => page.locator('[data-kp-document]');

async function makeKp(page: Page, company: string) {
    await page.locator('[data-kp-source="hero"]').click();
    await page.getByLabel('Название компании').fill(company);
    await page.getByRole('button', { name: 'Сформировать КП' }).click();
}

test('sheet feeds out like a printer, company is typed, then stamped', async ({ page }) => {
    await gotoClean(page);
    // Записываем все состояния листа (класс + напечатанный текст) — проверяем последовательность, а не тайминги.
    await page.evaluate(() => {
        const log: string[] = ((window as any).__kpLog = []);
        const d = document.querySelector('[data-kp-document]')!;
        const c = document.querySelector('[data-kp-company]')!;
        new MutationObserver(() => log.push(`${d.classList.contains('kp-printed') ? 'printed' : 'printing'}|${c.textContent}`)).observe(d, {
            attributes: true, subtree: true, childList: true, characterData: true,
        });
    });
    await makeKp(page, 'Ромашка');
    const company = page.locator('[data-kp-company]');
    await expect(doc(page)).toHaveClass(/kp-printed/, { timeout: 6000 });
    await expect(company).toHaveText('«Ромашка»');
    await expect(page.locator('[data-kp-stamp]')).toBeVisible();
    const log: string[] = await page.evaluate(() => (window as any).__kpLog);
    const partial = log.filter((s) => s.startsWith('printing|') && s.length > 'printing|'.length && !s.endsWith('«Ромашка»'));
    expect(partial.length, log.join('\n')).toBeGreaterThan(2); // буквы появлялись по одной
    expect(log.at(-1)).toBe('printed|«Ромашка»');
});

test('reduced motion: everything is shown at once', async ({ browser }) => {
    const ctx = await browser.newContext({ reducedMotion: 'reduce' });
    const page = await ctx.newPage();
    await gotoClean(page);
    await makeKp(page, 'Ромашка');
    await expect(doc(page)).toBeVisible();
    expect(await doc(page).getAttribute('class')).toContain('kp-printed');
    expect(await page.locator('[data-kp-company]').textContent()).toBe('«Ромашка»');
    await expect(page.locator('[data-kp-stamp]')).toBeVisible();
    await ctx.close();
});

test('downloading mid-typing finishes the text first', async ({ page }) => {
    await gotoClean(page);
    await makeKp(page, 'Очень длинное название компании для теста');
    await page.getByRole('button', { name: 'Скачать PDF' }).first().click();
    // ограничиваем окном: пока html2pdf работает, его копия листа тоже в DOM
    await expect(page.locator('[data-modal="kp-document"] [data-kp-company]')).toHaveText('«Очень длинное название компании для теста»', { timeout: 3000 });
});

test('a second KP reprints with the new company', async ({ page }) => {
    await gotoClean(page);
    await makeKp(page, 'Альфа');
    await expect(doc(page)).toHaveClass(/kp-printed/, { timeout: 4000 });
    await page.getByRole('button', { name: 'Закрыть' }).click();
    await page.evaluate(() => window.scrollTo(0, innerHeight * 2));
    await page.locator('[data-kp-source="floating"]').click();
    await page.getByLabel('Название компании').fill('Бета');
    await page.getByRole('button', { name: 'Сформировать КП' }).click();
    await expect(doc(page)).toHaveClass(/kp-printing/);
    await expect(page.locator('[data-kp-company]')).toHaveText('«Бета»', { timeout: 4000 });
});
