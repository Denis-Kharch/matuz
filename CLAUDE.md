# Сайт Андрея Матузова

- Спецификация: docs/superpowers/specs/2026-11-06-matuzov-site-design.md
- План этапа 1: docs/superpowers/plans/2026-11-06-matuzov-stage1-design.md
- Наполнение: только через `App\Content\ContentRepository` (данные в resources/content/*.php).
- Ссылки и ресурсы в шаблонах — только через url()/route()/asset()/Vite (сайт выгружается в подпапку GitHub Pages).
- Тесты: `./vendor/bin/pest`, `node --test tests/js/`, `npx playwright test`.
