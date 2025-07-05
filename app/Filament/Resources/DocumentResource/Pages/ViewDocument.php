<?php

namespace App\Filament\Resources\DocumentResource\Pages;

use App\Filament\Resources\DocumentResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Facades\Filament;
use App\Models\Document;

class ViewDocument extends ViewRecord
{
    protected static string $resource = DocumentResource::class;

    protected function getHeaderActions(): array
    {
        $currentUser = Filament::auth()->user();
        $isAdmin = DocumentResource::isCurrentUserAdmin();
        $isHeadOfDivision = DocumentResource::isCurrentUserHeadOfDivision();
        $currentUserDivisionId = DocumentResource::getCurrentUserDivisionId();

        // Ambil record Document yang sedang dilihat
        $record = $this->getRecord();

        // Apakah user ini kepala divisi DARI document yang sedang dilihat?
        $isHeadOfOwnDivisionDocument = $record->division_id === $currentUserDivisionId && $isHeadOfDivision;

        return [
            Actions\EditAction::make()
                ->visible(fn (): bool =>
                    $isAdmin || // Admin bisa melihat tombol Ubah untuk semua document
                    ($isHeadOfOwnDivisionDocument) // Kepala Divisi bisa melihat tombol Ubah untuk document divisinya
                ),
            Actions\DeleteAction::make()
                ->visible(fn (): bool =>
                    $isAdmin ||
                    ($isHeadOfOwnDivisionDocument)
                ),
        ];
    }
}