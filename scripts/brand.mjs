// Логотип: resources/brand/logo.png (отрисован из _source/logo-template.pptx, 1600×1600)
// → public/brand: шапка (webp), КП/PDF (png), иконки вкладки и телефона.
import fs from 'node:fs';
import sharp from 'sharp';

const src = 'resources/brand/logo.png';
const out = 'public/brand';
fs.mkdirSync(out, { recursive: true });
// Рамку слайда (1px по краю) срезаем, чтобы в маленьких размерах не было светлой каёмки.
const base = () => sharp(src).extract({ left: 4, top: 4, width: 1592, height: 1592 });

await base().resize(96).webp({ quality: 90 }).toFile(`${out}/logo-96.webp`);
await base().resize(192).png({ compressionLevel: 9 }).toFile(`${out}/logo-192.png`);
await base().resize(32).png({ compressionLevel: 9 }).toFile(`${out}/favicon-32.png`);
await base().resize(180).png({ compressionLevel: 9 }).toFile(`${out}/apple-touch-icon.png`);
console.log(fs.readdirSync(out).map((f) => `${f} ${fs.statSync(`${out}/${f}`).size}B`).join('\n'));
