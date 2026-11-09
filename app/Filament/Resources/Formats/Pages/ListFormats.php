<?php

namespace App\Filament\Resources\Formats\Pages;

use App\Filament\Resources\Formats\FormatResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListFormats extends ListRecords
{
    protected static string $resource = FormatResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
