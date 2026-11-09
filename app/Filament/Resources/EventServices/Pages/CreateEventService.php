<?php

namespace App\Filament\Resources\EventServices\Pages;

use App\Filament\Resources\EventServices\EventServiceResource;
use Filament\Resources\Pages\CreateRecord;

class CreateEventService extends CreateRecord
{
    protected static string $resource = EventServiceResource::class;
}
