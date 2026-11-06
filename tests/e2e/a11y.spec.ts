import { test, expect, type Page } from '@playwright/test';
import AxeBuilder from '@axe-core/playwright';
import { gotoClean } from './helpers';

async function audit(page: Page) {
    const results = await new AxeBuilder({ page }).withTags(['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa']).analyze();
    const serious = results.violations.filter((v) => v.impact === 'serious' || v.impact === 'critical');
    const report = serious.map((v) => `${v.id}: ${v.help} → ${v.nodes.slice(0, 3).map((n) => n.target.join(' ')).join(' | ')}`);
    expect(report, report.join('\n')).toEqual([]);
}

test('home has no serious a11y violations', async ({ page }) => {
    await gotoClean(page, '/');
    await audit(page);
});

test('events page has no serious a11y violations', async ({ page }) => {
    await gotoClean(page, '/events');
    await audit(page);
});

test('open case overlay has no serious a11y violations', async ({ page }) => {
    await gotoClean(page, '/cases/otrabotka-vozrazheniy-holodnye-zvonki');
    await expect(page.locator('[data-modal="case-otrabotka-vozrazheniy-holodnye-zvonki"]')).toBeVisible();
    await audit(page);
});

test('KP form and document have no serious a11y violations', async ({ page }) => {
    await gotoClean(page, '/');
    await page.locator('[data-kp-source="hero"]').click();
    await audit(page);
    await page.getByLabel('Название компании').fill('Ромашка');
    await page.getByRole('button', { name: 'Сформировать КП' }).click();
    await expect(page.locator('[data-kp-document]')).toBeVisible();
    await audit(page);
});
