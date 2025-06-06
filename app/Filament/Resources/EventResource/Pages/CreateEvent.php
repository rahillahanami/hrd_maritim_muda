<?php

namespace App\Filament\Resources\EventResource\Pages;

use App\Filament\Resources\EventResource; // Pastikan ini sudah ada (resource EventResource)
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use App\Models\Division; // Pastikan ini ada
use App\Models\User;     // Pastikan ini ada
use App\Models\Employee; // Pastikan ini ada

class CreateEvent extends CreateRecord
{
    protected static string $resource = EventResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $currentUser = \Filament\Facades\Filament::auth()->user();
        // Panggil helper dari App\Filament\Resources\EventResource dengan namespace lengkap
        $isAdmin = \App\Filament\Resources\EventResource::isCurrentUserAdmin(); // <<< UBAH BARIS INI

        Log::info('DEBUG (CreateEvent Page) - Initial data: ' . json_encode($data));
        Log::info('DEBUG (CreateEvent Page) - Current User ID: ' . ($currentUser->id ?? 'NULL') . ', Name: ' . ($currentUser->name ?? 'NULL'));
        Log::info('DEBUG (CreateEvent Page) - Is Admin: ' . ($isAdmin ? 'TRUE' : 'FALSE'));

        $data['created_by'] = $currentUser ? $currentUser->name : null;

        if (!$isAdmin) {
            // Panggil helper dari App\Filament\Resources\EventResource dengan namespace lengkap
            $isHeadOfDivision = \App\Filament\Resources\EventResource::isCurrentUserHeadOfDivision(); // <<< UBAH BARIS INI
            $currentUserDivisionId = \App\Filament\Resources\EventResource::getCurrentUserDivisionId(); // <<< UBAH BARIS INI
            Log::info('DEBUG (CreateEvent Page) - Is Head of Division: ' . ($isHeadOfDivision ? 'TRUE' : 'FALSE') . ', Current User Division ID: ' . ($currentUserDivisionId ?? 'NULL'));

            if ($isHeadOfDivision && $currentUserDivisionId) {
                $data['division_id'] = $currentUserDivisionId;
            } else {
                $data['division_id'] = null;
            }
        } else {
            if (isset($data['division_id']) && $data['division_id'] === 'null') {
                $data['division_id'] = null;
            }
        }

        Log::info('DEBUG (CreateEvent Page) - Final data before save: ' . json_encode($data));

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}