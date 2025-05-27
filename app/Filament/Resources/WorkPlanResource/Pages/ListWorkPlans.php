<?php

namespace App\Filament\Resources\WorkPlanResource\Pages;

use App\Filament\Resources\WorkPlanResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListWorkPlans extends ListRecords
{
    protected static string $resource = WorkPlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
