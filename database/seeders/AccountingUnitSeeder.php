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
        $incomingTupadRole = Role::firstOrCreate(['name' => 'Incoming-TUPAD']);
        $budgetOfficerRole = Role::firstOrCreate(['name' => 'Budget_Officer']);
        $outgoingRole = Role::firstOrCreate(['name' => 'Outgoing']);
         $accountingRole = Role::firstOrCreate(['name' => 'Accounting_III']);

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
                'profile_image' => '27.jpg',
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
        $imsd->assignRole($imsdRole);
         $imsd2 = AccountingUnit::firstOrCreate(
            ['email' => 'imsd2@email.com'],
            [
                'fname' => 'IMSD2',
                'mi' => 'I',
                'lname' => 'User2',
                'password' => ('password'),
                'profile_image' => 'default.png',
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        $imsd2->assignRole($imsdRole);

            $imsd3 = AccountingUnit::firstOrCreate(
            ['email' => 'imsd3@email.com'],
            [
                'fname' => 'IMSD3',
                'mi' => 'I',
                'lname' => 'User3',
                'password' => ('password'),
                'profile_image' => 'default.png',
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        $imsd3->assignRole($imsdRole);
        
        $incomingGip = AccountingUnit::firstOrCreate(
            ['email' => 'incoming.gip@email.com'],
            [
                'fname' => 'Incoming',
                'mi' => 'G',
                'lname' => 'IP',
                'password' => ('password'),
                'profile_image' => '30.jpg',
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
        $incomingGip->assignRole($incomingGipRole);

           $incomingGip2 = AccountingUnit::firstOrCreate(
            ['email' => 'incoming.gip2@email.com'],
            [
                'fname' => 'Incoming2',
                'mi' => 'G',
                'lname' => 'IP2',
                'password' => ('password'),
                'profile_image' => 'default.png',
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
        $incomingGip2->assignRole($incomingGipRole);

           $incomingGip3 = AccountingUnit::firstOrCreate(
            ['email' => 'incoming.gip2@email.com'],
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
        $incomingGip3->assignRole($incomingGipRole);


        $incomingTupad = AccountingUnit::firstOrCreate(
            ['email' => 'incoming.tupad@email.com'],
            [
                'fname' => 'Incoming',
                'mi' => 'T',
                'lname' => 'Tupad',
                'password' => ('password'),
                'profile_image' => 'default.png',
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
        $incomingTupad->assignRole($incomingTupadRole);

         $incomingTupad2 = AccountingUnit::firstOrCreate(
            ['email' => 'incoming.tupad2@email.com'],
            [
                'fname' => 'Incoming2',
                'mi' => 'T',
                'lname' => 'Tupad2',
                'password' => ('password'),
                'profile_image' => '35.jpg',
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
        $incomingTupad2->assignRole($incomingTupadRole);

         $incomingTupad3 = AccountingUnit::firstOrCreate(
            ['email' => 'incoming.tupad3@email.com'],
            [
                'fname' => 'Incoming3',
                'mi' => 'T',
                'lname' => 'Tupad3',
                'password' => ('password'),
                'profile_image' => 'default.png',
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
        $incomingTupad3->assignRole($incomingTupadRole);


        $budgetOfficer = AccountingUnit::firstOrCreate(
            ['email' => 'budget.officer@email.com'],
            [
                'fname' => 'Budget',
                'mi' => 'O',
                'lname' => 'Officer',
                'password' => ('password'),
                'profile_image' => '52.jpg',
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
        $budgetOfficer->assignRole($budgetOfficerRole);

         $budgetOfficer2 = AccountingUnit::firstOrCreate(
            ['email' => 'budget.officer2@email.com'],
            [
                'fname' => 'Budget2',
                'mi' => 'O',
                'lname' => 'Officer2',
                'password' => ('password'),
                'profile_image' => 'default.png',
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
        $budgetOfficer2->assignRole($budgetOfficerRole);

         $budgetOfficer3 = AccountingUnit::firstOrCreate(
            ['email' => 'budget.officer3@email.com'],
            [
                'fname' => 'Budget3',
                'mi' => 'O',
                'lname' => 'Officer3',
                'password' => ('password'),
                'profile_image' => 'default.png',
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
        $budgetOfficer3->assignRole($budgetOfficerRole);
        
        $outgoing = AccountingUnit::firstOrCreate(
            ['email' => 'outgoing@email.com'],
            [
                'fname' => 'Outgoing',
                'mi' => 'D',
                'lname' => 'Going',
                'password' => ('password'),
                'profile_image' => '37.jpg',
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
        $outgoing->assignRole($outgoingRole);

         $outgoing2 = AccountingUnit::firstOrCreate(
            ['email' => 'outgoing2@email.com'],
            [
                'fname' => 'Outgoing2',
                'mi' => 'D',
                'lname' => 'Going2',
                'password' => ('password'),
                'profile_image' => 'default.png',
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
        $outgoing2->assignRole($outgoingRole);

         $outgoing3 = AccountingUnit::firstOrCreate(
            ['email' => 'outgoing3@email.com'],
            [
                'fname' => 'Outgoing3',
                'mi' => 'D',
                'lname' => 'Going3',
                'password' => ('password'),
                'profile_image' => 'default.png',
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
        $outgoing3->assignRole($outgoingRole);

        $accounting = AccountingUnit::firstOrCreate(
            ['email' => 'accounting3@email.com'],
            [
                'fname' => 'Accounting',
                'mi' => 'A',
                'lname' => 'Accounting',
                'password' => ('password'),
                'profile_image' => '79.jpg',
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
        $accounting->assignRole($accountingRole);
    }
}
