// Сервер админки для e2e (Playwright webServer): отдельные база, storage и папка сайта
// в storage/e2e-admin — локальная база и public/ разработчика не трогаются.
// Каждый запуск: чистая база → content:import → пользователь e2e → php -S на порту (по умолчанию 4711).
import { spawn, spawnSync } from 'node:child_process';
import { cpSync, existsSync, mkdirSync, readFileSync, rmSync, writeFileSync } from 'node:fs';
import { dirname, join, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const repo = resolve(dirname(fileURLToPath(import.meta.url)), '..');
const port = process.env.E2E_ADMIN_PORT || '4730';
const root = join(repo, 'storage', 'e2e-admin');
const site = join(root, 'site');
const storage = join(root, 'storage');
const db = join(root, 'database.sqlite');

const E2E_USER = { email: 'e2e@example.test', password: 'e2e-password-123' };

rmSync(root, { recursive: true, force: true });
for (const d of ['app/private', 'app/public', 'framework/cache/data', 'framework/sessions', 'framework/views', 'logs']) {
    mkdirSync(join(storage, d), { recursive: true });
}
mkdirSync(site, { recursive: true });
// Только ресурсы сайта: без страниц прошлых публикаций и фото из базы разработчика.
for (const name of ['build', 'brand', 'img', 'css', 'js', 'fonts', 'favicon.ico', 'site.webmanifest']) {
    const from = join(repo, 'public', name);
    if (existsSync(from)) cpSync(from, join(site, name), { recursive: true });
}
// Laravel берёт LARAVEL_STORAGE_PATH из $_ENV/$_SERVER, а php -S кладёт окружение только в getenv().
const front = readFileSync(join(repo, 'deploy', 'regru', 'app.php'), 'utf8')
    .replaceAll('__APP_PATH__', repo.replaceAll('\\', '/'))
    .replace('<?php', "<?php\n$_SERVER['LARAVEL_STORAGE_PATH'] = getenv('LARAVEL_STORAGE_PATH');");
writeFileSync(join(site, 'index.php'), front);
writeFileSync(db, '');

const env = {
    ...process.env,
    APP_ENV: 'local',
    APP_DEBUG: 'true',
    // Не локальный адрес: публикация отказывается писать страницы со ссылками на 127.0.0.1/localhost.
    // Админка берёт адреса из запроса, а в опубликованных страницах будут ссылки на этот хост.
    APP_URL: 'http://matuzov.e2e.test',
    DB_CONNECTION: 'sqlite',
    DB_DATABASE: db,
    LARAVEL_STORAGE_PATH: storage,
    PUBLIC_SITE_PATH: site,
    CONTENT_SOURCE: 'database',
    SITE_AUTO_PUBLISH: 'true',
    SITE_PREVIEW: 'false',
    SESSION_DRIVER: 'file',
    CACHE_STORE: 'file',
    QUEUE_CONNECTION: 'sync',
};

function artisan(...args) {
    const r = spawnSync('php', ['artisan', ...args], { cwd: repo, env, encoding: 'utf8' });
    if (r.status !== 0) {
        console.error(r.stdout, r.stderr);
        throw new Error(`php artisan ${args.join(' ')} failed`);
    }
}

artisan('migrate:fresh', '--force');
artisan('content:import');
artisan('make:filament-user', '--name=E2E', `--email=${E2E_USER.email}`, `--password=${E2E_USER.password}`);

const router = join(repo, 'vendor', 'laravel', 'framework', 'src', 'Illuminate', 'Foundation', 'resources', 'server.php');
const server = spawn('php', ['-S', `127.0.0.1:${port}`, router], { cwd: site, env, stdio: 'ignore' });
const stop = () => server.kill();
process.on('SIGINT', stop);
process.on('SIGTERM', stop);
process.on('exit', stop);
server.on('exit', (code) => process.exit(code ?? 0));
