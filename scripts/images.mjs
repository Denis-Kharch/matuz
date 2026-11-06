// Нарезка фото: resources/images/** → public/img/**-<w>.(avif|webp) + public/img/manifest.json
// Ширины 640/1280/1920, но не больше исходника (никакого увеличения).
// Повторный запуск пропускает файлы, которые новее исходника.
import fs from 'node:fs';
import path from 'node:path';
import sharp from 'sharp';

const SRC = path.resolve('resources/images');
const OUT = path.resolve('public/img');
const WIDTHS = [640, 1280, 1920];
const EXT = /\.(png|jpe?g|webp|avif|tiff?)$/i;

const walk = (dir) =>
    fs.readdirSync(dir, { withFileTypes: true }).flatMap((e) => {
        const p = path.join(dir, e.name);
        return e.isDirectory() ? walk(p) : EXT.test(e.name) ? [p] : [];
    });

const manifest = {};
for (const file of walk(SRC)) {
    const key = path.relative(SRC, file).replace(EXT, '').split(path.sep).join('/');
    const meta = await sharp(file).metadata();
    const widths = WIDTHS.filter((w) => w <= meta.width);
    if (!widths.length || widths.at(-1) < meta.width) widths.push(Math.min(meta.width, 1920));
    const unique = [...new Set(widths)];
    const srcTime = fs.statSync(file).mtimeMs;
    fs.mkdirSync(path.join(OUT, path.dirname(key)), { recursive: true });
    for (const w of unique) {
        for (const [fmt, opts] of [
            ['avif', { quality: 50 }],
            ['webp', { quality: 72 }],
        ]) {
            const out = path.join(OUT, `${key}-${w}.${fmt}`);
            if (fs.existsSync(out) && fs.statSync(out).mtimeMs > srcTime) continue;
            await sharp(file).resize({ width: w, withoutEnlargement: true })[fmt](opts).toFile(out);
        }
    }
    manifest[key] = { widths: unique, width: meta.width, height: meta.height };
    console.log(`${key}: ${unique.join(', ')}`);
}
fs.writeFileSync(path.join(OUT, 'manifest.json'), JSON.stringify(manifest, null, 2));
