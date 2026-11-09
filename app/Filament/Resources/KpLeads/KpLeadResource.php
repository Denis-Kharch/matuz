<?php

namespace App\Filament\Resources\KpLeads;

use App\Filament\Resources\KpLeads\Pages\ListKpLeads;
use App\Models\KpLead;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class KpLeadResource extends Resource
{
    protected static ?string $model = KpLead::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInbox;

    protected static string|\UnitEnum|null $navigationGroup = 'Заявки';

    protected static ?string $modelLabel = 'заявка на КП';

    protected static ?string $pluralModelLabel = 'Заявки на КП';

    protected static ?int $navigationSort = 1;

    public static function getNavigationBadge(): ?string
    {
        $new = KpLead::whereNull('contacted_at')->count();

        return $new > 0 ? (string) $new : null;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')->label('Дата')->dateTime('d.m.Y H:i')->sortable(),
                TextColumn::make('company')->label('Компания')->searchable(),
                TextColumn::make('city')->label('Город')->placeholder('—'),
                TextColumn::make('source')->label('Источник'),
                IconColumn::make('contacted_at')->label('Связались')->boolean()
                    ->getStateUsing(fn (KpLead $record): bool => $record->contacted_at !== null),
            ])
            ->recordActions([
                Action::make('contacted')
                    ->label('Отметить: связались')
                    ->icon(Heroicon::OutlinedCheck)
                    ->visible(fn (KpLead $record): bool => $record->contacted_at === null)
                    ->action(fn (KpLead $record) => $record->update(['contacted_at' => now()])),
                Action::make('uncontacted')
                    ->label('Снять отметку')
                    ->icon(Heroicon::OutlinedXMark)
                    ->color('gray')
                    ->visible(fn (KpLead $record): bool => $record->contacted_at !== null)
                    ->action(fn (KpLead $record) => $record->update(['contacted_at' => null])),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListKpLeads::route('/')];
    }
}
