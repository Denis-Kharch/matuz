// Проверка статичной выгрузки: node scripts/check-export.mjs <dist> <siteUrl>
// siteUrl — адрес сайта с подпапкой, например https://denis-kharch.github.io/matuz
// Падает (exit 1), если:
//  - ссылка/ресурс начинается с «/» без базового пути (на Pages сайт живёт в подпапке);
//  - есть ссылки на localhost / 127.0.0.1;
//  - локальная ссылка ведёт на несуществующий файл;
//  - нет обязательных страниц.
import fs from 'node:fs';
import path from 'node:path';

const [distArg, siteArg] = process.argv.slice(2);
if (!distArg || !siteArg) {
    console.error('usage: node scripts/check-export.mjs <dist> <siteUrl>');
    process.exit(2);
}
const dist = path.resolve(distArg);
const site = new URL(siteArg.replace(/\/$/, '') + '/');
const base = site.pathname.replace(/\/$/, ''); // '' или '/matuz'
const problems = [];

const walk = (dir) =>
    fs.readdirSync(dir, { withFileTypes: true }).flatMap((e) => {
        const p = path.join(dir, e.name);
        return e.isDirectory() ? walk(p) : [p];
    });

const files = walk(dist);
const htmlFiles = files.filter((f) => f.endsWith('.html'));

for (const required of ['index.html', 'events/index.html', '404.html', 'sitemap.xml', 'robots.txt', '.nojekyll']) {
    if (!fs.existsSync(path.join(dist, required))) problems.push(`нет обязательного файла: ${required}`);
}

const toLocalFile = (pathname) => {
    let rel = decodeURIComponent(pathname.slice(base.length)).replace(/^\//, '');
    if (rel === '' || rel.endsWith('/')) rel += 'index.html';
    else if (!path.extname(rel)) rel += '/index.html';
    return path.join(dist, rel);
};

const ATTR = /\s(href|src|srcset|content|action)="([^"]*)"/g;

for (const file of htmlFiles) {
    const rel = path.relative(dist, file);
    const html = fs.readFileSync(file, 'utf8');
    if (/localhost|127\.0\.0\.1/.test(html)) problems.push(`${rel}: ссылка на localhost/127.0.0.1`);

    for (const [, attr, value] of html.matchAll(ATTR)) {
        const candidates = attr === 'srcset' ? value.split(',').map((s) => s.trim().split(/\s+/)[0]) : [value];
        for (const raw of candidates) {
            if (!raw || raw.startsWith('#') || /^(mailto:|tel:|data:|javascript:)/.test(raw)) continue;
            if (attr === 'content' && !/^(https?:)?\//.test(raw)) continue;
            if (raw.startsWith('/') && !raw.startsWith('//')) {
                if (base && !(raw === base || raw.startsWith(base + '/'))) problems.push(`${rel}: путь от корня без базы «${base}»: ${raw}`);
                continue;
            }
            let url;
            try {
                url = new URL(raw, site);
            } catch {
                continue;
            }
            if (url.origin !== site.origin) continue; // внешняя ссылка
            if (!(url.pathname === base || url.pathname.startsWith(base + '/'))) {
                problems.push(`${rel}: ссылка вне сайта «${base}»: ${raw}`);
                continue;
            }
            const target = toLocalFile(url.pathname);
            if (!fs.existsSync(target)) problems.push(`${rel}: нет файла для ${raw}`);
        }
    }
}

// url(...) в собранном CSS (шрифты, картинки).
for (const file of files.filter((f) => f.endsWith('.css'))) {
    const rel = path.relative(dist, file);
    const css = fs.readFileSync(file, 'utf8');
    for (const [, rawQuoted] of css.matchAll(/url\(([^)]+)\)/g)) {
        const raw = rawQuoted.replace(/^['"]|['"]$/g, '');
        if (raw.startsWith('data:') || raw.startsWith('#')) continue;
        if (raw.startsWith('/') && !raw.startsWith('//') && base && !raw.startsWith(base + '/')) {
            problems.push(`${rel}: url() от корня без базы «${base}»: ${raw}`);
            continue;
        }
        const url = new URL(raw, raw.startsWith('http') ? undefined : new URL(path.relative(dist, path.dirname(file)).split(path.sep).join('/') + '/', site));
        if (url.origin !== site.origin) continue;
        if (!fs.existsSync(toLocalFile(url.pathname))) problems.push(`${rel}: нет файла для url(${raw})`);
    }
}

if (problems.length) {
    console.error(`Проблем: ${problems.length}`);
    for (const p of [...new Set(problems)].slice(0, 80)) console.error('  - ' + p);
    process.exit(1);
}
console.log(`OK: ${htmlFiles.length} HTML-страниц, ${files.length} файлов, база «${base || '/'}»`);
