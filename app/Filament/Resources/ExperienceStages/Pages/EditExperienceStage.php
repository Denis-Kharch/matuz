<?php

namespace App\Filament\Resources\ExperienceStages\Pages;

use App\Filament\Resources\ExperienceStages\ExperienceStageResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditExperienceStage extends EditRecord
{
    protected static string $resource = ExperienceStageResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
