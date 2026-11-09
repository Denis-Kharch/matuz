<?php

namespace App\Filament\Resources\TopicGroups\Pages;

use App\Filament\Resources\TopicGroups\TopicGroupResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTopicGroups extends ListRecords
{
    protected static string $resource = TopicGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
