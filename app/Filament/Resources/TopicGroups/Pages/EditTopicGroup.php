<?php

namespace App\Filament\Resources\TopicGroups\Pages;

use App\Filament\Resources\TopicGroups\TopicGroupResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTopicGroup extends EditRecord
{
    protected static string $resource = TopicGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
