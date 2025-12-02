<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\AccountingUnit;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;
use Faker\Factory as Faker;

class AccountingUnitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
       $faker = Faker::create();
        // Create roles
        $adminRole = Role::firstOrCreate(['name' => 'Admin']);
        $imsdRole = Role::firstOrCreate(['name' => 'IMSD']);
        $incomingGipRole = Role::firstOrCreate(['name' => 'Incoming-GIP']);
        $incomingTupadRole = Role::firstOrCreate(['name' => 'Incoming-TUPAD']);
        $incomingTevRole = Role::firstOrCreate(['name' => 'Incoming-TEV']);
        $budgetOfficerRole = Role::firstOrCreate(['name' => 'Budget_Officer']);
        $outgoingRole = Role::firstOrCreate(['name' => 'Outgoing']);
        $accountingRole = Role::firstOrCreate(['name' => 'Accountant_III']); // Changed to Accountant_III
        $regionalDirectorRole = Role::firstOrCreate(['name' => 'Regional_Director']);
        $cashierRole = Role::firstOrCreate(['name' => 'Cashier']);

        // All available roles for random assignment
        $allRoles = [
            $adminRole, $imsdRole, $incomingGipRole, $incomingTupadRole, $incomingTevRole,
            $budgetOfficerRole, $outgoingRole, $accountingRole, $regionalDirectorRole, $cashierRole
        ];

        // All roles except admin for regular users
        $regularUserRoles = [
            $imsdRole, $incomingGipRole, $incomingTupadRole, $incomingTevRole,
            $budgetOfficerRole, $outgoingRole, $accountingRole, $regionalDirectorRole, $cashierRole
        ];

        // Create Admin account with ALL roles
        $admin = AccountingUnit::firstOrCreate(
            ['email' => 'admin@email.com'],
            [
                'fname' => 'Kadyn',
                'mi' => 'A',
                'lname' => 'Curtis',
                'password' => 'password',
                'profile_image' => 'default.png',
                'email_verified_at' => now(),
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
        $admin->syncRoles($allRoles); // Admin gets ALL roles
        $admin->update(['primary_role' => 'Admin']);

        // Create users with multiple random roles
        $users = [];

        // IMSD Users (2-3 roles including IMSD)
        for ($i = 1; $i <= 3; $i++) {
            $user = AccountingUnit::firstOrCreate(
                ['email' => "imsd{$i}@email.com"],
                [
                    'fname' => $faker->firstName(),
                    'mi' => $faker->randomLetter(),
                    'lname' => $faker->lastName(),
                    'password' => 'password',
                    'profile_image' => $i == 1 ? '27.jpg' : 'default.png',
                    'email_verified_at' => now(),
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
            
            // Assign 2-3 random roles including IMSD
            $roles = $faker->randomElements($regularUserRoles, $faker->numberBetween(2, 3));
            if (!in_array($imsdRole, $roles)) {
                $roles[0] = $imsdRole; // Ensure IMSD role is included
            }
            $user->syncRoles($roles);
            $user->update(['primary_role' => $imsdRole->name]);
            $users[] = $user;
        }

        // Incoming GIP Users (2-3 roles including Incoming-GIP)
        for ($i = 1; $i <= 3; $i++) {
            $user = AccountingUnit::firstOrCreate(
                ['email' => "incoming.gip{$i}@email.com"],
                [
                    'fname' => $faker->firstName(),
                    'mi' => $faker->randomLetter(),
                    'lname' => $faker->lastName(),
                    'password' => 'password',
                    'profile_image' => $i == 1 ? '30.jpg' : 'default.png',
                    'email_verified_at' => now(),
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
            
            // Assign 2-3 random roles including Incoming-GIP
            $roles = $faker->randomElements($regularUserRoles, $faker->numberBetween(2, 3));
            if (!in_array($incomingGipRole, $roles)) {
                $roles[0] = $incomingGipRole;
            }
            $user->syncRoles($roles);
            $user->update(['primary_role' => $incomingGipRole->name]);
            $users[] = $user;
        }

        // Incoming TUPAD Users (2-3 roles including Incoming-TUPAD)
        for ($i = 1; $i <= 3; $i++) {
            $user = AccountingUnit::firstOrCreate(
                ['email' => "incoming.tupad{$i}@email.com"],
                [
                    'fname' => $faker->firstName(),
                    'mi' => $faker->randomLetter(),
                    'lname' => $faker->lastName(),
                    'password' => 'password',
                    'profile_image' => $i == 2 ? '35.jpg' : 'default.png',
                    'email_verified_at' => now(),
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
            
            // Assign 2-3 random roles including Incoming-TUPAD
            $roles = $faker->randomElements($regularUserRoles, $faker->numberBetween(2, 3));
            if (!in_array($incomingTupadRole, $roles)) {
                $roles[0] = $incomingTupadRole;
            }
            $user->syncRoles($roles);
            $user->update(['primary_role' => $incomingTupadRole->name]);
            $users[] = $user;
        }

        // Incoming TEV User (2-3 roles including Incoming-TEV)
        $incomingTev = AccountingUnit::firstOrCreate(
            ['email' => 'incoming.tev@email.com'],  
            [
                'fname' => $faker->firstName(),
                'mi' => $faker->randomLetter(),
                'lname' => $faker->lastName(),
                'password' => 'password',
                'profile_image' => 'default.png',
                'email_verified_at' => now(),
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
        
        // Assign 2-3 random roles including Incoming-TEV
        $roles = $faker->randomElements($regularUserRoles, $faker->numberBetween(2, 3));
        if (!in_array($incomingTevRole, $roles)) {
            $roles[0] = $incomingTevRole;
        }
        $incomingTev->syncRoles($roles);
        $incomingTev->update(['primary_role' => $incomingTevRole->name]);

        // Budget Officers (2-3 roles including Budget_Officer)
        for ($i = 1; $i <= 3; $i++) {
            $user = AccountingUnit::firstOrCreate(
                ['email' => "budget.officer{$i}@email.com"],
                [
                    'fname' => $faker->firstName(),
                    'mi' => $faker->randomLetter(),
                    'lname' => $faker->lastName(),
                    'password' => 'password',
                    'profile_image' => $i == 1 ? '52.jpg' : 'default.png',
                    'email_verified_at' => now(),
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
            
            // Assign 2-3 random roles including Budget_Officer
            $roles = $faker->randomElements($regularUserRoles, $faker->numberBetween(2, 3));
            if (!in_array($budgetOfficerRole, $roles)) {
                $roles[0] = $budgetOfficerRole;
            }
            $user->syncRoles($roles);
            $user->update(['primary_role' => $budgetOfficerRole->name]);
            $users[] = $user;
        }

        // Outgoing Users (2-3 roles including Outgoing)
        for ($i = 1; $i <= 3; $i++) {
            $user = AccountingUnit::firstOrCreate(
                ['email' => "outgoing{$i}@email.com"],
                [
                    'fname' => $faker->firstName(),
                    'mi' => $faker->randomLetter(),
                    'lname' => $faker->lastName(),
                    'password' => 'password',
                    'profile_image' => $i == 1 ? '37.jpg' : 'default.png',
                    'email_verified_at' => now(),
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
            
            // Assign 2-3 random roles including Outgoing
            $roles = $faker->randomElements($regularUserRoles, $faker->numberBetween(2, 3));
            if (!in_array($outgoingRole, $roles)) {
                $roles[0] = $outgoingRole;
            }
            $user->syncRoles($roles);
            $user->update(['primary_role' => $outgoingRole->name]);
            $users[] = $user;
        }

        // Accounting III (2-3 roles including Accounting_III)
        $accounting = AccountingUnit::firstOrCreate(
            ['email' => 'accounting3@email.com'],
            [
                'fname' => $faker->firstName(),
                'mi' => $faker->randomLetter(),
                'lname' => $faker->lastName(),
                'password' => 'password',
                'profile_image' => '79.jpg',
                'email_verified_at' => now(),
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
        
        // Assign 2-3 random roles including Accounting_III
        $roles = $faker->randomElements($regularUserRoles, $faker->numberBetween(2, 3));
        if (!in_array($accountingRole, $roles)) {
            $roles[0] = $accountingRole;
        }
        $accounting->syncRoles($roles);
        $accounting->update(['primary_role' => $accountingRole->name]);

        // Regional Director (2-3 roles including Regional_Director)
        $rd = AccountingUnit::firstOrCreate(
            ['email' => 'regional.director@email.com'],
            [
                'fname' => $faker->firstName(),
                'mi' => $faker->randomLetter(),
                'lname' => $faker->lastName(),
                'password' => 'password',
                'profile_image' => 'default.png',
                'email_verified_at' => now(),
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
        
        // Assign 2-3 random roles including Regional_Director
        $roles = $faker->randomElements($regularUserRoles, $faker->numberBetween(2, 3));
        if (!in_array($regionalDirectorRole, $roles)) {
            $roles[0] = $regionalDirectorRole;
        }
        $rd->syncRoles($roles);
        $rd->update(['primary_role' => $regionalDirectorRole->name]);

        // Cashier (2-3 roles including Cashier)
        $cash = AccountingUnit::firstOrCreate(
            ['email' => 'cashier@email.com'],
            [
                'fname' => $faker->firstName(),
                'mi' => $faker->randomLetter(),
                'lname' => $faker->lastName(),
                'password' => 'password',
                'profile_image' => 'default.png',
                'email_verified_at' => now(),
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
        
        // Assign 2-3 random roles including Cashier
        $roles = $faker->randomElements($regularUserRoles, $faker->numberBetween(2, 3));
        if (!in_array($cashierRole, $roles)) {
            $roles[0] = $cashierRole;
        }
        $cash->syncRoles($roles);
        $cash->update(['primary_role' => $cashierRole->name]);

        // Output summary
        $this->command->info('Admin user created with ALL roles: ' . $admin->getRoleNames()->implode(', '));
        $this->command->info('Total users created: ' . (count($users) + 5)); // +5 for the single role users
    }
}