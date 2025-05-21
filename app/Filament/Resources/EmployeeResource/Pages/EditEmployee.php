<?php

namespace App\Filament\Resources\EmployeeResource\Pages;

use App\Filament\Resources\EmployeeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditEmployee extends EditRecord
{
    protected static string $resource = EmployeeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->after(function (EditEmployee $livewire) {
                    // When the employee is deleted, also delete the associated user
                    if ($livewire->record->user) {
                        $livewire->record->user->delete();
                    }
                }),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // If there's an associated user, add their email for editing
        if ($this->record->user) {
            $data['email'] = $this->record->user->email;
        }

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        // Update the User model
        if ($record->user) {
            $record->user->update([
                'name' => $data['name'],
                'email' => $data['email'],
            ]);

            // Only update password if it was provided
            if (isset($data['password']) && $data['password']) {
                $record->user->update([
                    'password' => $data['password'],
                ]);
            }
        }

        // Update the Employee model
        $record->update([
            'name' => $data['name'],
            'gender' => $data['gender'],
            'birth_date' => $data['birth_date'],
            'phone_number' => $data['phone_number'],
            'address' => $data['address'],
            'division_id' => $data['division_id'],
        ]);

        return $record;
    }
}
