<?php

namespace App\Filament\Resources\EventServices\Pages;

use App\Filament\Resources\EventServices\EventServiceResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditEventService extends EditRecord
{
    protected static string $resource = EventServiceResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
