import { test, expect, type Page } from '@playwright/test';
import { gotoClean } from './helpers';

const form = (page: Page) => page.getByRole('dialog', { name: 'Моментальное КП' });
const doc = (page: Page) => page.locator('[data-kp-document]');

test.beforeEach(async ({ page }) => gotoClean(page));

test('empty company shows error, no KP', async ({ page }) => {
    await page.locator('[data-kp-source="hero"]').click();
    await expect(form(page)).toBeVisible();
    await expect(page.getByLabel('Название компании')).toBeFocused();
    await page.getByRole('button', { name: 'Сформировать КП' }).click();
    await expect(page.getByText('Укажите название компании')).toBeVisible();
    await expect(doc(page)).toBeHidden();
});

test('whitespace-only company is rejected', async ({ page }) => {
    await page.locator('[data-kp-source="hero"]').click();
    await page.getByLabel('Название компании').fill('    ');
    await page.getByRole('button', { name: 'Сформировать КП' }).click();
    await expect(page.getByText('Укажите название компании')).toBeVisible();
});

test('company with quotes and markup renders escaped and personalised', async ({ page }) => {
    await page.locator('[data-kp-source="hero"]').click();
    await page.getByLabel('Название компании').fill('ООО «Ромашка» <b>x</b>');
    await page.getByLabel('Город').fill('Казань');
    await page.getByRole('button', { name: 'Сформировать КП' }).click();
    await expect(doc(page)).toBeVisible();
    await expect(doc(page)).toContainText('ООО «Ромашка» <b>x</b>');
    await expect(doc(page)).toContainText('Казань');
    await expect(doc(page).locator('b', { hasText: /^x$/ })).toHaveCount(0);
    await expect(form(page)).toBeHidden();
});

test('very long company name does not break the layout', async ({ page }) => {
    await page.locator('[data-kp-source="hero"]').click();
    await page.getByLabel('Название компании').fill('Очень'.repeat(60));
    await page.getByRole('button', { name: 'Сформировать КП' }).click();
    await expect(doc(page)).toBeVisible();
    // ждём конца анимаций (печать «АМ» на старте увеличена и временно выходит за край листа)
    await doc(page).evaluate((el) => Promise.all(el.getAnimations({ subtree: true }).filter((a) => a.effect?.getTiming().iterations !== Infinity).map((a) => a.finished)));
    const overflow = await doc(page).evaluate((el) => el.scrollWidth > el.clientWidth + 1);
    expect(overflow).toBe(false);
});

test('city is optional', async ({ page }) => {
    await page.locator('[data-kp-source="hero"]').click();
    await page.getByLabel('Название компании').fill('Тест');
    await page.getByRole('button', { name: 'Сформировать КП' }).click();
    await expect(doc(page)).toBeVisible();
    await expect(doc(page).locator('[data-kp-city]')).toBeHidden();
});

test('PDF downloads with sanitised name', async ({ page }) => {
    await page.locator('[data-kp-source="hero"]').click();
    await page.getByLabel('Название компании').fill('ООО «Ромашка»');
    await page.getByRole('button', { name: 'Сформировать КП' }).click();
    const dl = page.waitForEvent('download', { timeout: 30_000 });
    await page.getByRole('button', { name: 'Скачать PDF' }).first().click();
    expect((await dl).suggestedFilename()).toBe('КП_Матузов_ООО_Ромашка.pdf');
});

test('PDF render contains company, city and date (Alpine must not wipe the clone)', async ({ page }) => {
    await page.evaluate(() => {
        (window as any).__pdfText = '';
        new MutationObserver((muts) => {
            for (const m of muts)
                for (const n of m.addedNodes) {
                    if (n instanceof HTMLElement && n.className?.toString().includes('html2pdf__')) {
                        setTimeout(() => ((window as any).__pdfText = n.textContent), 50);
                    }
                }
        }).observe(document.body, { childList: true });
    });
    await page.locator('[data-kp-source="hero"]').click();
    await page.getByLabel('Название компании').fill('ООО Ромашка');
    await page.getByLabel('Город').fill('Казань');
    await page.getByRole('button', { name: 'Сформировать КП' }).click();
    const dl = page.waitForEvent('download', { timeout: 30_000 });
    await page.getByRole('button', { name: 'Скачать PDF' }).first().click();
    await dl;
    const text = await page.evaluate(() => (window as any).__pdfText as string);
    expect(text).toContain('«ООО Ромашка»');
    expect(text).toContain('Казань');
    expect(text).toMatch(/\d{2}\.\d{2}\.\d{4}/);
});

test('falls back to print when PDF library fails to load', async ({ page }) => {
    await page.route(/html2pdf/i, (r) => r.abort());
    await page.evaluate(() => {
        (window as any).__printed = false;
        window.print = () => {
            (window as any).__printed = true;
        };
    });
    await page.locator('[data-kp-source="hero"]').click();
    await page.getByLabel('Название компании').fill('Тест');
    await page.getByRole('button', { name: 'Сформировать КП' }).click();
    await page.getByRole('button', { name: 'Скачать PDF' }).first().click();
    await expect.poll(() => page.evaluate(() => (window as any).__printed), { timeout: 15_000 }).toBe(true);
});

test('print fallback prints the KP sheet on a few pages, not repeated screens', async ({ page, browserName }) => {
    test.skip(browserName !== 'chromium');
    await page.locator('[data-kp-source="hero"]').click();
    await page.getByLabel('Название компании').fill('Ромашка');
    await page.getByRole('button', { name: 'Сформировать КП' }).click();
    await expect(page.locator('[data-kp-document]')).toBeVisible();
    const pdf = (await page.pdf({ format: 'A4' })).toString('latin1');
    const pages = (pdf.match(/\/Type\s*\/Page[^s]/g) ?? []).length;
    expect(pages).toBeGreaterThanOrEqual(2);
    expect(pages).toBeLessThanOrEqual(6);
});

test('company already in quotes is not wrapped in extra quotes', async ({ page }) => {
    await page.locator('[data-kp-source="hero"]').click();
    await page.getByLabel('Название компании').fill('ООО «Пример»');
    await page.getByRole('button', { name: 'Сформировать КП' }).click();
    await expect(doc(page)).toContainText('для компании ООО «Пример»');
    await expect(doc(page)).not.toContainText('«ООО «Пример»»');
});

test('plain company name gets quotes', async ({ page }) => {
    await page.locator('[data-kp-source="hero"]').click();
    await page.getByLabel('Название компании').fill('Ромашка');
    await page.getByRole('button', { name: 'Сформировать КП' }).click();
    await expect(doc(page)).toContainText('для компании «Ромашка»');
});

test('KP from inside an open case replaces the case overlay', async ({ page }) => {
    const card = page.locator('[data-case-card]').first();
    await card.scrollIntoViewIfNeeded();
    await card.click();
    await page.locator('[data-modal^="case-"]:visible [data-kp-source="case"]').click();
    await expect(page.locator('[data-modal^="case-"]:visible')).toHaveCount(0);
    await expect(form(page)).toBeVisible();
    await expect(page).not.toHaveURL(/\/cases\//);
});

async function addEndpoint(page: Page, url: string) {
    await page.evaluate((u) => {
        const m = document.createElement('meta');
        m.name = 'kp-endpoint';
        m.content = u;
        document.head.append(m);
    }, url);
}

test('lead is sent with source when endpoint meta exists (stage 3 hook)', async ({ page }) => {
    const requests: any[] = [];
    await page.route('**/kp-lead-test', async (r) => {
        requests.push(r.request().postDataJSON());
        await r.fulfill({ status: 204 });
    });
    await addEndpoint(page, '/kp-lead-test');
    await page.locator('[data-kp-source="hero"]').click();
    await page.getByLabel('Название компании').fill('Ромашка');
    await page.getByLabel('Город').fill('Казань');
    await page.getByRole('button', { name: 'Сформировать КП' }).click();
    await expect.poll(() => requests.length).toBe(1);
    expect(requests[0]).toMatchObject({ company: 'Ромашка', city: 'Казань', source: 'hero' });
});

test('failing lead endpoint does not block the KP', async ({ page }) => {
    await page.route('**/kp-lead-fail', (r) => r.fulfill({ status: 500 }));
    await addEndpoint(page, '/kp-lead-fail');
    await page.locator('[data-kp-source="hero"]').click();
    await page.getByLabel('Название компании').fill('Ромашка');
    await page.getByRole('button', { name: 'Сформировать КП' }).click();
    await expect(doc(page)).toBeVisible();
});
