<?php

namespace App\Filament\Resources\LeaveResource\Pages;

use App\Filament\Resources\LeaveResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Facades\Filament; // Pastikan ini ada

class CreateLeave extends CreateRecord
{
    protected static string $resource = LeaveResource::class;

    // protected function getFormActions(): array
    // {

    //     $actions = [
    //         Actions\CreateAction::make()
    //             ->url($this->getResource()::getUrl('index')), // Alihkan ke halaman daftar setelah membuat
    //     ];

    //     // Tidak ada tombol "Buat & buat lainnya" yang akan ditambahkan

    //     // Tombol "Batal"
    //     $actions[] = Actions\Action::make('cancel')
    //         ->label('Batal')
    //         ->color('gray')
    //         ->url($this->getResource()::getUrl('index')); // Kembali ke halaman daftar

    //     return $actions;
    // }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $currentUser = Filament::auth()->user();

        // Tambahkan user_id ke data yang akan disimpan
        $data['user_id'] = $currentUser->id;

        // Opsional: Anda bisa menambahkan logika lain di sini jika perlu

        return $data;
    }
    // Opsional: Jika Anda tidak ingin redirect ke halaman edit setelah create
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}