<?php

namespace App\Filament\Resources\WorkPlanResource\Pages;

use App\Filament\Resources\WorkPlanResource;
use Filament\Actions;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;

class CreateWorkPlan extends CreateRecord
{
    protected static string $resource = WorkPlanResource::class;

    public function mutateFormDataBeforeCreate(array $data): array
    {
        $currentUser = Filament::auth()->user();
        $isAdmin = WorkPlanResource::isCurrentUserAdmin(); // Call the method from WorkPlanResource
        $isHeadOfDivision = WorkPlanResource::isCurrentUserHeadOfDivision(); // Call the method from WorkPlanResource
        $currentUserDivisionId = WorkPlanResource::getCurrentUserDivisionId(); // Call the method from WorkPlanResource

        // Set division_id if not already set
        if (!isset($data['division_id']) && !$isAdmin && $isHeadOfDivision) {
            $data['division_id'] = $currentUserDivisionId;
        }

        // Set user_id to the current user
        $data['user_id'] = $currentUser->id ?? null;

        return $data;
    }
}
