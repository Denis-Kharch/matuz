import { test, expect, type APIRequestContext } from '@playwright/test';

// Сервер админки — scripts/e2e-admin-server.mjs (отдельная база и папка сайта, см. playwright.config.ts).
const USER = { email: 'e2e@example.test', password: 'e2e-password-123' };
const CASE = { slug: 'otrabotka-vozrazheniy-holodnye-zvonki', title: 'Отработка возражений в холодных звонках' };

/** Ключи фото из таблицы media на странице: «id-хеш». */
async function mediaKeys(request: APIRequestContext, path: string): Promise<string[]> {
    const res = await request.get(path);
    if (!res.ok()) return [];
    return [...new Set([...(await res.text()).matchAll(/media\/(\d+-[0-9a-f]+)-\d+\.webp/g)].map((m) => m[1]))];
}

test('crop a case photo in the admin and see the new variant on the published page', async ({ page, request }) => {
    test.setTimeout(180_000);
    const before = await mediaKeys(request, `/cases/${CASE.slug}`);
    expect(before.length).toBeGreaterThan(0);

    await page.goto('/admin/login');
    // Вход только по паролю — поля почты нет.
    await expect(page.locator('[id="form.email"]')).toHaveCount(0);
    await page.locator('[id="form.password"]').fill(USER.password);
    await page.getByRole('button', { name: 'Войти' }).click();
    // Первый запуск компилирует шаблоны Filament в чистый storage — даём время.
    await expect(page).toHaveURL(/\/admin\/?$/, { timeout: 60_000 });

    await page.goto('/admin/case-studies');
    await page.getByRole('link', { name: CASE.title }).first().click();
    await expect(page).toHaveURL(/\/admin\/case-studies\/\d+\/edit/);

    const photos = page.locator('[data-image-field="photos"]');
    await photos.getByRole('button', { name: 'Кадрировать' }).first().click();

    const box = page.locator('.mcf-modal:visible .cropper-crop-box');
    await expect(box).toBeVisible();
    const b = (await box.boundingBox())!;
    expect(Math.abs(b.width / b.height - 4 / 3)).toBeLessThan(0.02);

    await page.locator('.mcf-modal:visible').getByRole('button', { name: 'Повернуть', exact: true }).click();
    await page.locator('.mcf-modal:visible').getByRole('button', { name: 'Применить' }).click();
    await expect(page.locator('.mcf-modal:visible')).toHaveCount(0);
    await expect(photos.locator('.mcf__status').first()).toContainText('поворот 90°');

    await page.getByRole('button', { name: /^Сохранить/ }).first().click();
    // Ответ на сохранение ждёт нарезку фото (и под php -S — публикацию сайта).
    await expect(page.getByText('Сохранено')).toBeVisible({ timeout: 90_000 });

    // Публикация идёт после ответа: ждём опубликованный файл страницы кейса с новым вариантом фото.
    await expect
        .poll(async () => {
            const after = await mediaKeys(request, `/cases/${CASE.slug}/index.html`);
            const ids = new Set(before.map((k) => k.split('-')[0]));
            return after.some((k) => ids.has(k.split('-')[0]) && !before.includes(k));
        }, { timeout: 60_000 })
        .toBe(true);
});
