<?php

namespace App\Filament\Resources\Partners\Pages;

use App\Filament\Resources\Partners\PartnerResource;
use App\Models\Setting;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;

class ListPartners extends ListRecords
{
    protected static string $resource = PartnerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('display')
                ->label('Вид блока')
                ->icon(Heroicon::OutlinedAdjustmentsHorizontal)
                ->color('gray')
                ->modalHeading('Вид блока «Партнёры» на сайте')
                ->modalSubmitActionLabel('Сохранить')
                ->fillForm(fn (): array => [
                    'marquee' => (bool) (Setting::get('partners_display', [])['marquee'] ?? false),
                    'mobile_only' => (bool) (Setting::get('partners_display', [])['mobile_only'] ?? false),
                ])
                ->schema([
                    Toggle::make('marquee')->label('Бегущая строка')->live()
                        ->helperText('Логотипы едут одной строкой — блок занимает сильно меньше места.'),
                    Toggle::make('mobile_only')->label('Только для мобильной версии')
                        ->helperText('На телефоне — бегущая строка, на компьютере — компактная сетка логотипов.')
                        ->visible(fn (Get $get): bool => (bool) $get('marquee')),
                ])
                ->action(function (array $data): void {
                    // Setting::put сам ставит пересборку сайта.
                    Setting::put('partners_display', [
                        'marquee' => (bool) ($data['marquee'] ?? false),
                        'mobile_only' => (bool) ($data['marquee'] ?? false) && (bool) ($data['mobile_only'] ?? false),
                    ]);
                    Notification::make()->title('Сохранено')->body('Сайт обновится в течение минуты.')->success()->send();
                }),
            CreateAction::make(),
        ];
    }
}
