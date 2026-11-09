<?php

namespace App\Filament\Resources\Principles\Pages;

use App\Filament\Resources\Principles\PrincipleResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPrinciple extends EditRecord
{
    protected static string $resource = PrincipleResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
