<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;

class SpecificUsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Ensure Super Admin Role exists
        $superAdminRole = Role::firstOrCreate(['name' => 'super_admin']);

        // 2. Update or Create sadmin@intalnet.com
        $sadmin = User::updateOrCreate(
            ['email' => 'sadmin@intalnet.com'],
            [
                'name' => 'Super Admin Intalnet',
                'password' => Hash::make('password'),
                'city' => 'Monteria',
            ]
        );
        $sadmin->assignRole($superAdminRole);
        $this->command->info('User sadmin@intalnet.com updated/created as Super Admin (City: Monteria).');

        // 3. Update or Create test@intalnet.com
        User::updateOrCreate(
            ['email' => 'test@intalnet.com'],
            [
                'name' => 'Test User 1',
                'password' => Hash::make('1234567'),
                'city' => 'Monteria',
            ]
        );
        $this->command->info('User test@intalnet.com updated/created.');

        // 4. Update or Create test2@intalnet.com
        User::updateOrCreate(
            ['email' => 'test2@intalnet.com'],
            [
                'name' => 'Test User 2',
                'password' => Hash::make('1234567'),
                'city' => 'Monteria',
            ]
        );
        $this->command->info('User test2@intalnet.com updated/created.');
    }
}
