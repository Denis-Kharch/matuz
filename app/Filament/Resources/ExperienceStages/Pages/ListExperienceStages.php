<?php

namespace App\Filament\Resources\ExperienceStages\Pages;

use App\Filament\Resources\ExperienceStages\ExperienceStageResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListExperienceStages extends ListRecords
{
    protected static string $resource = ExperienceStageResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
