<?php

namespace App\Providers;

use App\Content\ContentRepository;
use App\Content\StaticContentRepository;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(ContentRepository::class, StaticContentRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // $c — наполнение сайта во всех шаблонах.
        View::share('c', $this->app->make(ContentRepository::class));
    }
}
