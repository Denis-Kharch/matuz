// Логотип и иконки сайта.
// Источник: resources/brand/logo.png (отрисован PowerPoint из _source/logo-template.pptx, 1600×1600).
//  • Полный логотип (MÁ/TU/ZOV.RU): шапка, КП/PDF, apple-touch-icon, иконки манифеста.
//  • Знак «MÁ» (буквы вырезаны из логотипа) на тёмном фоне с бронзовыми полосами — для маленьких
//    иконок (16–120 px), где полный логотип нечитаем: favicon.ico, favicon-32/48/96/120.
import fs from 'node:fs';
import sharp from 'sharp';

const SRC = 'resources/brand/logo.png';
const OUT = 'public/brand';
const BG = '#0a1113';
const BRONZE = '#cd9456';
fs.mkdirSync(OUT, { recursive: true });

// Полный логотип без рамки слайда (1px по краю).
const logo = () => sharp(SRC).extract({ left: 4, top: 4, width: 1592, height: 1592 });

// Знак: буквы «MÁ» с ударением (координаты в исходнике 1600×1600) на квадрате 512.
async function markPng(size) {
    const S = 512;
    const letters = await sharp(SRC).extract({ left: 262, top: 170, width: 650, height: 410 }).resize({ width: 385 }).png().toBuffer();
    const meta = await sharp(letters).metadata();
    const shapes = Buffer.from(`<svg xmlns="http://www.w3.org/2000/svg" width="${S}" height="${S}">
        <rect width="${S}" height="${S}" fill="${BG}"/>
        <polygon points="${S * 0.7},0 ${S},0 ${S},${S * 0.62}" fill="${BRONZE}"/>
        <polygon points="0,${S * 0.8} 0,${S} ${S * 0.12},${S}" fill="${BRONZE}"/>
    </svg>`);
    const left = Math.round((S - meta.width) / 2) - 34;
    const top = Math.round((S - meta.height) / 2) + 6;
    const big = await sharp(shapes).composite([{ input: letters, left, top }]).png().toBuffer();
    return sharp(big).resize(size, size, { kernel: 'lanczos3' }).png({ compressionLevel: 9 }).toBuffer();
}

// ICO с PNG-кадрами внутри (поддерживают все современные браузеры и поисковики).
function ico(pngs) {
    const header = Buffer.alloc(6);
    header.writeUInt16LE(0, 0); // reserved
    header.writeUInt16LE(1, 2); // type: icon
    header.writeUInt16LE(pngs.length, 4);
    let offset = 6 + 16 * pngs.length;
    const entries = pngs.map(({ size, data }) => {
        const e = Buffer.alloc(16);
        e.writeUInt8(size >= 256 ? 0 : size, 0);
        e.writeUInt8(size >= 256 ? 0 : size, 1);
        e.writeUInt8(0, 2); // палитра
        e.writeUInt8(0, 3);
        e.writeUInt16LE(1, 4); // planes
        e.writeUInt16LE(32, 6); // bpp
        e.writeUInt32LE(data.length, 8);
        e.writeUInt32LE(offset, 12);
        offset += data.length;
        return e;
    });
    return Buffer.concat([header, ...entries, ...pngs.map((p) => p.data)]);
}

// Логотип: шапка, КП/PDF.
await logo().resize(96).webp({ quality: 90 }).toFile(`${OUT}/logo-96.webp`);
await logo().resize(192).png({ compressionLevel: 9 }).toFile(`${OUT}/logo-192.png`);

// Иконки устройств (крупные — полный логотип).
await logo().resize(180).png({ compressionLevel: 9 }).toFile(`${OUT}/apple-touch-icon.png`);
await logo().resize(192).png({ compressionLevel: 9 }).toFile(`${OUT}/icon-192.png`);
await logo().resize(512).png({ compressionLevel: 9 }).toFile(`${OUT}/icon-512.png`);

// Иконки вкладки и поисковиков (знак «MÁ»).
for (const size of [32, 48, 96, 120]) fs.writeFileSync(`${OUT}/favicon-${size}.png`, await markPng(size));
fs.writeFileSync('public/favicon.ico', ico(await Promise.all([16, 32, 48].map(async (size) => ({ size, data: await markPng(size) })))));
fs.writeFileSync(`${OUT}/mark-512.png`, await markPng(512)); // для проверки глазами

console.log(['public/favicon.ico', ...fs.readdirSync(OUT).map((f) => `${OUT}/${f}`)].map((f) => `${f} ${fs.statSync(f).size}B`).join('\n'));
