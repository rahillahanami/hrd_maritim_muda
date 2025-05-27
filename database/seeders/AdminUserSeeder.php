<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Buat user admin jika belum ada
        $user = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password'), // Ganti password sebelum live!
            ]
        );

        // 2. Buat role super_admin jika belum ada
        $role = Role::firstOrCreate(['name' => 'super_admin']);

        // 3. Ambil semua permission dan berikan ke role super_admin
        $permissions = Permission::all();
        $role->syncPermissions($permissions);

        // 4. Assign role ke user admin
        $user->assignRole($role);

        // 5. (Opsional) Berikan langsung semua permission ke user juga
        $user->syncPermissions($permissions);

        // 6. Info tambahan
        $this->command->info('User admin berhasil dibuat: admin@example.com / password');
    }
}
