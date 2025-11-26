<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Crear Rol Super Admin
        $role = Role::firstOrCreate(['name' => 'super_admin']);

        // 2. Crear Usuario Admin (o buscar si existe)
        $user = User::firstOrCreate(
            ['email' => 'admin@admin.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password'), // Contraseña por defecto
            ]
        );

        // 3. Asignar Rol
        $user->assignRole($role);

        $this->command->info('Super Admin created successfully.');
        $this->command->info('Email: admin@admin.com');
        $this->command->info('Password: password');
    }
}
