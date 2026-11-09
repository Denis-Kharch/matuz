<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Dashboard;
use App\Http\Controllers\Admin\MediaOriginalController;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Vite;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->brandName('Андрей Матузов')
            ->brandLogo(asset('brand/logo-96.webp'))
            ->brandLogoHeight('2.25rem')
            ->favicon(asset('brand/favicon-32.png'))
            ->colors([
                'primary' => Color::hex('#ED9954'),
            ])
            ->navigationGroups(['Заявки', 'Контент', 'Страница', 'КП', 'Настройки'])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->authenticatedRoutes(function (): void {
                Route::get('media/{media}/original', MediaOriginalController::class)->name('media.original');
            })
            // Окно кадрирования (Cropper.js) — отдельная сборка vite.admin.config.js (public/build/admin).
            // Копия Vite: общий экземпляр рендерит и публичный сайт (публикация в том же запросе).
            ->renderHook(PanelsRenderHook::HEAD_END, fn (): Htmlable => (clone app(Vite::class))
                ->useBuildDirectory('build/admin')
                ->useHotFile(public_path('admin.hot'))(['resources/js/admin/crop-field.js']));
    }
}
