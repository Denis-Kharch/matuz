<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\PublishStatusWidget;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $title = 'Главная';

    public function getWidgets(): array
    {
        return [PublishStatusWidget::class];
    }
}
