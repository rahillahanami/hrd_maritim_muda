<?php

namespace App\Filament\Resources\ResignationResource\Pages;

use App\Filament\Resources\ResignationResource;
use App\Models\Resignation;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;

class CreateResignation extends CreateRecord
{
    protected static string $resource = ResignationResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $currentUser = Filament::auth()->user();

        // Cek apakah sudah ada pengajuan pending
        $existing = Resignation::where('user_id', $currentUser->id)
            ->where('status', 'Pending')
            ->exists();

        if ($existing) {
            // Tampilkan notifikasi
            Notification::make()
                ->title('Pengajuan Resign Sudah Ada')
                ->body('Anda sudah memiliki pengajuan resign yang masih Pending.')
                ->danger()
                ->send();

            // Redirect balik tanpa menyimpan
            $this->redirect(ResignationResource::getUrl()); // kembali ke halaman index

            // Hentikan proses tanpa exception
            return [];
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return ResignationResource::getUrl(); // Setelah berhasil buat, kembali ke index
    }
}
