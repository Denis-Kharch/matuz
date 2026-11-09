import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

// Ресурсы админки (окно кадрирования Cropper.js) — отдельной сборкой в public/build/admin,
// чтобы бандл публичного сайта не менялся. Подключает AdminPanelProvider.
export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/js/admin/crop-field.js'],
            buildDirectory: 'build/admin',
            hotFile: 'public/admin.hot',
            refresh: false,
        }),
    ],
});
