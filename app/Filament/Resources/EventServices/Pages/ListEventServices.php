<?php

namespace App\Filament\Resources\EventServices\Pages;

use App\Filament\Resources\EventServices\EventServiceResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListEventServices extends ListRecords
{
    protected static string $resource = EventServiceResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
