// Скриншоты для визуальной проверки: node scripts/shoot.mjs <url> <out-prefix> [selector]
// Ширины 1440/1280/768/375. Cookie-выбор выставлен заранее.
import { chromium } from '@playwright/test';
const [url, out, selector] = process.argv.slice(2);
const browser = await chromium.launch();
for (const [w, h] of [[1440, 900], [768, 1024], [375, 812]]) {
    const page = await browser.newPage({ viewport: { width: w, height: h }, deviceScaleFactor: 1 });
    await page.addInitScript(() => { try { localStorage.setItem('am_cookie_choice', 'accepted'); } catch {} });
    await page.goto(url, { waitUntil: 'networkidle' });
    await page.evaluate(() => document.fonts.ready);
    if (selector) {
        await page.locator(selector).first().scrollIntoViewIfNeeded();
        await page.locator(selector).first().screenshot({ path: `${out}-${w}.png` });
    } else {
        await page.screenshot({ path: `${out}-${w}.png` });
    }
    await page.close();
}
await browser.close();
