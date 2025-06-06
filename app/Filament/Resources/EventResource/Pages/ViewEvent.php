<?php

namespace App\Filament\Resources\EventResource\Pages;

use App\Filament\Resources\EventResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Facades\Filament; // Pastikan ini di-import
use App\Models\Event; // Pastikan ini di-import

class ViewEvent extends ViewRecord
{
    protected static string $resource = EventResource::class;

    protected function getHeaderActions(): array
    {
        $currentUser = Filament::auth()->user();
        $isAdmin = \App\Filament\Resources\EventResource::isCurrentUserAdmin();
        $isHeadOfDivision = \App\Filament\Resources\EventResource::isCurrentUserHeadOfDivision();
        $currentUserDivisionId = \App\Filament\Resources\EventResource::getCurrentUserDivisionId();

        // Ambil record Event yang sedang dilihat
        $record = $this->getRecord();

        // Apakah user ini kepala divisi DARI event yang sedang dilihat?
        // (Logika sama dengan isEditingOwnDivisionEvent)
        $isHeadOfOwnDivisionEvent = $record->division_id === $currentUserDivisionId && $isHeadOfDivision;

        return [
            Actions\EditAction::make()
                ->visible(fn (): bool =>
                    $isAdmin || // Admin bisa melihat tombol Ubah untuk semua event
                    ($isHeadOfOwnDivisionEvent) // Kepala Divisi bisa melihat tombol Ubah untuk event divisinya
                ),
            // Mungkin ada tombol DeleteAction di sini juga yang perlu dibatasi?
            // Actions\DeleteAction::make()
            //     ->visible(fn (): bool =>
            //         $isAdmin ||
            //         ($isHeadOfOwnDivisionEvent)
            //     ),
        ];
    }
}