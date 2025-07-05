<?php

namespace App\Filament\Resources\WorkPlanResource\Pages;

use App\Filament\Resources\WorkPlanResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Facades\Filament;
use App\Models\WorkPlan;

class ViewWorkPlan extends ViewRecord
{
    protected static string $resource = WorkPlanResource::class;

    protected function getHeaderActions(): array
    {
        $currentUser = Filament::auth()->user();
        $isAdmin = WorkPlanResource::isCurrentUserAdmin();
        $isHeadOfDivision = WorkPlanResource::isCurrentUserHeadOfDivision();
        $currentUserDivisionId = WorkPlanResource::getCurrentUserDivisionId();

        // Ambil record WorkPlan yang sedang dilihat
        $record = $this->getRecord();

        // Apakah user ini kepala divisi DARI work plan yang sedang dilihat?
        $isHeadOfOwnDivisionWorkPlan = $record->division_id === $currentUserDivisionId && $isHeadOfDivision;

        return [
            Actions\EditAction::make()
                ->visible(fn (): bool =>
                    $isAdmin || // Admin bisa melihat tombol Ubah untuk semua work plan
                    ($isHeadOfOwnDivisionWorkPlan) // Kepala Divisi bisa melihat tombol Ubah untuk work plan divisinya
                ),
            Actions\DeleteAction::make()
                ->visible(fn (): bool =>
                    $isAdmin ||
                    ($isHeadOfOwnDivisionWorkPlan)
                ),
        ];
    }
}