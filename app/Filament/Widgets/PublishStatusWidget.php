<?php

namespace App\Filament\Widgets;

use App\Models\Setting;
use App\Site\SitePublisher;
use Carbon\CarbonImmutable;
use Filament\Notifications\Notification;
use Filament\Widgets\Widget;
use Throwable;

class PublishStatusWidget extends Widget
{
    protected string $view = 'filament.widgets.publish-status';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = -10;

    protected static bool $isLazy = false;

    public function rebuild(): void
    {
        try {
            $result = app(SitePublisher::class)->publishNow();
        } catch (Throwable $e) {
            report($e);
            Notification::make()->title('Ошибка: '.$e->getMessage())->danger()->send();

            return;
        }

        if ($result === null) {
            Notification::make()->title('Сайт уже обновляется, изменения попадут в него автоматически')->warning()->send();

            return;
        }
        if ($result->ok) {
            Notification::make()->title('Сайт обновлён')->success()->send();
        } else {
            Notification::make()->title('Ошибка: '.$result->error)->danger()->send();
        }
    }

    protected function getViewData(): array
    {
        $status = Setting::get('publish_status');
        $at = is_array($status) && ! empty($status['at'])
            ? CarbonImmutable::parse($status['at'])->setTimezone(config('app.timezone'))
            : null;

        return [
            'status' => is_array($status) ? $status : null,
            'time' => $at?->format('H:i'),
            'date' => $at?->translatedFormat('j F'),
        ];
    }
}
