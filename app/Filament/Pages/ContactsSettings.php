<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use Filament\Forms\Components\TextInput;
use Filament\Support\Icons\Heroicon;

class ContactsSettings extends SettingsPage
{
    protected static string|\UnitEnum|null $navigationGroup = 'Страница';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedPhone;

    protected static ?string $navigationLabel = 'Контакты';

    protected static ?string $title = 'Контакты';

    protected static ?string $slug = 'contacts';

    protected static ?int $navigationSort = 3;

    protected function fields(): array
    {
        return [
            TextInput::make('phone')->label('Телефон')->required()->maxLength(50)
                ->rule('regex:/^\+?[\d\s()\-]{10,20}$/')
                ->validationMessages(['regex' => 'Введите номер в формате +7 918 642 88 95.'])
                ->helperText('Ссылка для звонка («tel:») строится сама.'),
            TextInput::make('vk')->label('VK')->url()->maxLength(255),
            TextInput::make('telegram')->label('Telegram')->url()->maxLength(255),
            TextInput::make('max')->label('MAX')->url()->maxLength(255),
        ];
    }

    protected function load(): array
    {
        return Setting::get('contacts', []);
    }

    protected function persist(array $state): void
    {
        $phone = trim((string) $state['phone']);
        Setting::put('contacts', [
            'phone' => $phone,
            'phone_href' => self::phoneHref($phone),
            'vk' => (string) ($state['vk'] ?? ''),
            'telegram' => (string) ($state['telegram'] ?? ''),
            'max' => (string) ($state['max'] ?? ''),
        ]);
    }

    /** «+7 918 642 88 95» и «8 (918) 642-88-95» → tel:+79186428895. */
    public static function phoneHref(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone);
        if (strlen($digits) === 11 && $digits[0] === '8') {
            $digits = '7'.substr($digits, 1);
        }

        return 'tel:+'.$digits;
    }
}
