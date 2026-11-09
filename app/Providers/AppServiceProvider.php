<?php

namespace App\Providers;

use App\Content\ContentRepository;
use App\Content\DatabaseContentRepository;
use App\Content\StaticContentRepository;
use App\Site\NullPublishQueue;
use App\Site\PublishQueue;
use App\Site\SitePublisher;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(StaticContentRepository::class);
        $this->app->singleton(DatabaseContentRepository::class);
        // Источник читается в момент обращения: config('site.content_source') можно переключить без перезапуска.
        $this->app->bind(ContentRepository::class, fn ($app) => $app->make(
            config('site.content_source') === 'database' ? DatabaseContentRepository::class : StaticContentRepository::class
        ));
        // Сохранения в админке пересобирают сайт. В тестах выключено (SITE_AUTO_PUBLISH=false в phpunit.xml),
        // чтобы каждое сохранение модели не рендерило весь сайт; тесты публикации включают явно.
        $this->app->singleton(SitePublisher::class);
        $this->app->singleton(NullPublishQueue::class);
        $this->app->bind(PublishQueue::class, fn ($app) => $app->make(
            config('site.auto_publish') ? SitePublisher::class : NullPublishQueue::class
        ));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // $c — наполнение сайта во всех шаблонах.
        // Через composer, а не share: источник выбирается при отрисовке, не при загрузке приложения.
        View::composer('*', fn ($view) => $view->with('c', $this->app->make(ContentRepository::class)));
    }
}
