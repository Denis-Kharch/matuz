# Сайт Андрея Матузова

- Спецификация: docs/superpowers/specs/2026-11-06-matuzov-site-design.md
- План этапа 1: docs/superpowers/plans/2026-11-06-matuzov-stage1-design.md
- Наполнение: только через `App\Content\ContentRepository` (данные в resources/content/*.php).
- Ссылки и ресурсы в шаблонах — только через url()/route()/asset()/Vite (сайт выгружается в подпапку GitHub Pages).
- Тесты: `./vendor/bin/pest`, `node --test tests/js/`, `npx playwright test`.
- Статичная выгрузка (как на GitHub Pages): `APP_URL=<url> ASSET_URL=<url> npm run build && APP_URL=<url> ASSET_URL=<url> php artisan site:export`, проверка — `node scripts/check-export.mjs dist <url>`, локальный просмотр — `MSYS_NO_PATHCONV=1 node scripts/serve-dist.mjs dist 4720 /<repo>`.
- Фото: исходники в resources/images, нарезка `npm run images` (public/img не в git).
- Windows: npm/node с `NODE_OPTIONS="--dns-result-order=ipv4first --no-network-family-autoselection"` (иначе таймауты по IPv6); порт 8040–8139 зарезервирован системой.
- Логотип: `_source/logo-template.pptx` → отрисован в `resources/brand/logo.png` → `node scripts/brand.mjs` делает `public/brand/*` (шапка, КП, favicon).
- Свой домен на Pages: переменная репозитория `SITE_URL` (Settings → Secrets and variables → Actions → Variables) + DNS у регистратора; workflow сам кладёт CNAME.
- Хостинг reg.ru (сайт + админка): `.github/workflows/deploy-regru.yml`, push в main при `REGRU_DEPLOY=ftp` (SSH на хостинге нет). FTPS: приложение одним архивом → `REGRU_APP_PATH/storage/app/deploy/app.zip`, распаковка `deploy-unpack.php` (из `deploy/regru/unpack.php`) в папке сайта; приложение в `REGRU_APP_PATH` (вне www, метка `.matuzov-app`, `.env` из `deploy/regru/env.template` создаётся один раз), в папку сайта `REGRU_PATH` — только `build/`, `brand/`, `app.php`, `.htaccess`, `.user.ini`, иконки (страницы и `media/` пишет приложение). Затем `POST /_deploy/{DEPLOY_TOKEN}` (`DeployHookController`): база, миграции, импорт наполнения, первый админ, `filament:assets`, публикация, `optimize`. Секреты и чек-лист — `docs/deploy-regru.md`. GitHub Pages (`pages.yml`) — только ручной запуск.
