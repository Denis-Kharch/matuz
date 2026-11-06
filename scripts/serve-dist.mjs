// Статичный сервер выгрузки, как GitHub Pages: node scripts/serve-dist.mjs <dist> <port> <base>
// Пример: node scripts/serve-dist.mjs dist 4720 /matuz  →  http://127.0.0.1:4720/matuz/
import http from 'node:http';
import fs from 'node:fs';
import path from 'node:path';
import zlib from 'node:zlib';

const [distArg = 'dist', port = '4720', base = ''] = process.argv.slice(2);
const dist = path.resolve(distArg);
const types = {
    '.html': 'text/html; charset=utf-8', '.css': 'text/css', '.js': 'text/javascript', '.json': 'application/json',
    '.svg': 'image/svg+xml', '.webp': 'image/webp', '.avif': 'image/avif', '.png': 'image/png', '.ico': 'image/x-icon',
    '.woff2': 'font/woff2', '.xml': 'application/xml', '.txt': 'text/plain; charset=utf-8',
};

http.createServer((req, res) => {
    let p = decodeURIComponent(new URL(req.url, 'http://x').pathname);
    if (base && !p.startsWith(base)) return send404(res);
    p = p.slice(base.length) || '/';
    let file = path.join(dist, p);
    if (!file.startsWith(dist)) return send404(res);
    if (fs.existsSync(file) && fs.statSync(file).isDirectory()) {
        if (!p.endsWith('/')) {
            res.writeHead(301, { Location: base + p + '/' });
            return res.end();
        }
        file = path.join(file, 'index.html');
    }
    if (!fs.existsSync(file)) return send404(res);
    const type = types[path.extname(file)] ?? 'application/octet-stream';
    // Как GitHub Pages: текстовые файлы — gzip.
    if (/text|javascript|json|xml|svg/.test(type) && /gzip/.test(req.headers['accept-encoding'] ?? '')) {
        res.writeHead(200, { 'Content-Type': type, 'Content-Encoding': 'gzip' });
        return fs.createReadStream(file).pipe(zlib.createGzip()).pipe(res);
    }
    res.writeHead(200, { 'Content-Type': type });
    fs.createReadStream(file).pipe(res);
}).listen(Number(port), '127.0.0.1', () => console.log(`http://127.0.0.1:${port}${base}/`));

function send404(res) {
    res.writeHead(404, { 'Content-Type': 'text/html; charset=utf-8' });
    fs.createReadStream(path.join(dist, '404.html')).pipe(res);
}
