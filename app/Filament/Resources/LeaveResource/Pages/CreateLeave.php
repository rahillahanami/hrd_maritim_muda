<?php

namespace App\Filament\Resources\LeaveResource\Pages;

use App\Filament\Resources\LeaveResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Facades\Filament; // Pastikan ini ada

class CreateLeave extends CreateRecord
{
    protected static string $resource = LeaveResource::class;

    protected function getFormActions(): array
    {
        // Karena hanya user biasa yang bisa create Leave (sesuai canCreate()),
        // dan mereka tidak butuh "Buat & buat lainnya", kita bisa langsung definisikan aksinya.
        // Tidak perlu cek $isAdmin di sini.

        $actions = [
            Actions\CreateAction::make()
                ->url($this->getResource()::getUrl('index')), // Alihkan ke halaman daftar setelah membuat
        ];

        // Tidak ada tombol "Buat & buat lainnya" yang akan ditambahkan

        // Tombol "Batal"
        $actions[] = Actions\Action::make('cancel')
            ->label('Batal')
            ->color('gray')
            ->url($this->getResource()::getUrl('index')); // Kembali ke halaman daftar

        return $actions;
    }

    // Opsional: Jika Anda tidak ingin redirect ke halaman edit setelah create
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}