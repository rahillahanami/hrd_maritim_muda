<?php

namespace App\Filament\Resources\EmployeeResource\Pages;

use App\Filament\Resources\EmployeeResource;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateEmployee extends CreateRecord
{
    protected static string $resource = EmployeeResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        DB::beginTransaction();
        // Create the User model first
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'] ?? null,
            'email_verified_at' => now(),
        ]);
        $user->assignRole($data['roles'] ?? []);

        // Create the Employee model with a reference to the User
        $employee = static::getModel()::create([
            'user_id' => $user->id,
            'name' => $data['name'],
            'gender' => $data['gender'],
            'birth_date' => $data['birth_date'],
            'phone_number' => $data['phone_number'],
            'address' => $data['address'],
            'division_id' => $data['division_id'] ?? null,
            'nip' => $data['nip'],
        ]);

        DB::commit();
        return $employee;
    }
}
