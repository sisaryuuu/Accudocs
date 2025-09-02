<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\AccountingUnit;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;

class AccountingUnitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create roles
        $adminRole = Role::firstOrCreate(['name' => 'Admin']);
        $imsdRole = Role::firstOrCreate(['name' => 'IMSD']);
        $incomingGipRole = Role::firstOrCreate(['name' => 'Incoming-GIP']);

        // Create Admin account
        $admin = AccountingUnit::firstOrCreate(
            ['email' => 'admin@email.com'], // unique identifier
            [
                'fname' => 'Admin',
                'mi' => 'A',
                'lname' => 'Admin',
                'password' => ('password'),
                'profile_image' => 'default.png',
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
        $admin->assignRole($adminRole);

        // Example: IMSD user
        $imsd = AccountingUnit::firstOrCreate(
            ['email' => 'imsd@email.com'],
            [
                'fname' => 'IMSD',
                'mi' => 'I',
                'lname' => 'User',
                'password' => ('password'),
                'profile_image' => 'default.png',
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
        $imsd->assignRole($imsdRole);

        // Example: Incoming-GIP user
        $incomingGip = AccountingUnit::firstOrCreate(
            ['email' => 'incoming.gip@email.com'],
            [
                'fname' => 'Incoming',
                'mi' => 'G',
                'lname' => 'IP',
                'password' => ('password'),
                'profile_image' => 'default.png',
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
        $incomingGip->assignRole($incomingGipRole);
    }
}
