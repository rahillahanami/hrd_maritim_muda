<?php

namespace App\Filament\Resources\ResignationResource\Pages;

use App\Filament\Resources\ResignationResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Facades\Filament;

class CreateResignation extends CreateRecord
{
    protected static string $resource = ResignationResource::class;

    protected function getFormActions(): array
    {

        $actions = [
            Actions\CreateAction::make()
                ->url($this->getResource()::getUrl('index')), // Alihkan ke halaman daftar setelah membuat
        ];

        // Tombol "Batal"
        $actions[] = Actions\Action::make('cancel')
            ->label('Batal')
            ->color('gray')
            ->url($this->getResource()::getUrl('index'));

        return $actions;
    }

    // Opsional: Jika Anda tidak ingin redirect ke halaman edit setelah create
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}