<?php

namespace App\Filament\Resources\Formats\Pages;

use App\Filament\Resources\Formats\FormatResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditFormat extends EditRecord
{
    protected static string $resource = FormatResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
