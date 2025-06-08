<?php

namespace App\Filament\Resources\ResignationResource\Pages;

use App\Filament\Resources\ResignationResource;
use App\Models\Resignation;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Illuminate\Validation\ValidationException;

class CreateResignation extends CreateRecord
{
    protected static string $resource = ResignationResource::class;

    // Method 1: Check before form is displayed (Recommended)
    public function mount(): void
    {
        parent::mount();
        
        $currentUser = Filament::auth()->user();

        // Check if user already has a pending resignation
        $existing = Resignation::where('user_id', $currentUser->id)
            ->where('status', 'Pending')
            ->exists();

        if ($existing) {
            // Show notification
            Notification::make()
                ->title('Pengajuan Resign Sudah Ada')
                ->body('Anda sudah memiliki pengajuan resign yang masih Pending. Silakan tunggu hingga diproses.')
                ->danger()
                ->persistent() // Make notification stay longer
                ->send();

            // Redirect back to index page
            $this->redirect(ResignationResource::getUrl());
            return;
        }
    }

    // Method 2: Validate before saving (Additional safety)
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $currentUser = Filament::auth()->user();

        // Double-check before creating (in case someone bypasses the mount check)
        $existing = Resignation::where('user_id', $currentUser->id)
            ->where('status', 'Pending')
            ->exists();

        if ($existing) {
            // Throw validation exception to prevent saving
           Notification::make()
                ->title('Pengajuan Resign Sudah Ada')
                ->body('Anda sudah memiliki pengajuan resign yang masih Pending. Silakan tunggu hingga diproses.')
                ->danger()
                ->persistent() // Make notification stay longer
                ->send();
        }

        // Add current user ID to the data
        $data['user_id'] = $currentUser->id;

        return $data;
    }

    // Override form actions to remove "Create & create another" button
    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction(),
            $this->getCancelFormAction(),
        ];
    }

    // Method 3: Alternative approach - Override the create method
    /*
    public function create(bool $another = false): void
    {
        $currentUser = Filament::auth()->user();

        // Check for pending resignation
        $existing = Resignation::where('user_id', $currentUser->id)
            ->where('status', 'Pending')
            ->exists();

        if ($existing) {
            Notification::make()
                ->title('Pengajuan Resign Sudah Ada')
                ->body('Anda sudah memiliki pengajuan resign yang masih Pending.')
                ->danger()
                ->send();
            
            return; // Stop execution
        }

        // Continue with normal creation process
        parent::create($another);
    }
    */

    protected function getRedirectUrl(): string
    {
        return ResignationResource::getUrl(); // Redirect to index after successful creation
    }

    // Optional: Add success notification after creation
    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Pengajuan Resign Berhasil')
            ->body('Pengajuan resign Anda telah berhasil diajukan dan sedang menunggu persetujuan.');
    }
}