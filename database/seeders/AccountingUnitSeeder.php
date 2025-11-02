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
        $incomingTevRole = Role::firstOrCreate(['name' => 'Incoming-TEV']);
        $budgetOfficerRole = Role::firstOrCreate(['name' => 'Budget_Officer']);
        $outgoingRole = Role::firstOrCreate(['name' => 'Outgoing']);
        $accountingRole = Role::firstOrCreate(['name' => 'Accounting_III']);
        $regionalDirectorRole = Role::firstOrCreate(['name' => 'Regional_Director']);
        $cashierRole = Role::firstOrCreate(['name' => 'Cashier']);

        // Create Admin account
        $admin = AccountingUnit::firstOrCreate(
            ['email' => 'admin@email.com'],
            [
                'fname' => 'Admin',
                'mi' => 'A',
                'lname' => 'Admin',
                'password' => 'password',
                'profile_image' => 'default.png',
                'email_verified_at' => now(), // ✅ Add verified timestamp
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
        $admin->assignRole($adminRole);

        // IMSD Users
        $imsd = AccountingUnit::firstOrCreate(
            ['email' => 'imsd@email.com'],
            [
                'fname' => 'IMSD',
                'mi' => 'I',
                'lname' => 'User',
                'password' => 'password',
                'profile_image' => '27.jpg',
                'email_verified_at' => now(), // ✅
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
                'password' => 'password',
                'profile_image' => 'default.png',
                'email_verified_at' => now(), // ✅
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
                'password' => 'password',
                'profile_image' => 'default.png',
                'email_verified_at' => now(), // ✅
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
        $imsd3->assignRole($imsdRole);
        
        // Incoming GIP Users
        $incomingGip = AccountingUnit::firstOrCreate(
            ['email' => 'incoming.gip@email.com'],
            [
                'fname' => 'Incoming',
                'mi' => 'G',
                'lname' => 'IP',
                'password' => 'password',
                'profile_image' => '30.jpg',
                'email_verified_at' => now(), // ✅
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
                'password' => 'password',
                'profile_image' => 'default.png',
                'email_verified_at' => now(), // ✅
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
        $incomingGip2->assignRole($incomingGipRole);

        $incomingGip3 = AccountingUnit::firstOrCreate(
            ['email' => 'incoming.gip3@email.com'], // ✅ Fixed duplicate email
            [
                'fname' => 'Incoming3',
                'mi' => 'G',
                'lname' => 'IP3',
                'password' => 'password',
                'profile_image' => 'default.png',
                'email_verified_at' => now(), // ✅
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
        $incomingGip3->assignRole($incomingGipRole);

        // Incoming TUPAD Users
        $incomingTupad = AccountingUnit::firstOrCreate(
            ['email' => 'incoming.tupad@email.com'],
            [
                'fname' => 'Incoming',
                'mi' => 'T',
                'lname' => 'Tupad',
                'password' => 'password',
                'profile_image' => 'default.png',
                'email_verified_at' => now(), // ✅
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
                'password' => 'password',
                'profile_image' => '35.jpg',
                'email_verified_at' => now(), // ✅
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
                'password' => 'password',
                'profile_image' => 'default.png',
                'email_verified_at' => now(), // ✅
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
        $incomingTupad3->assignRole($incomingTupadRole);

        // Incoming TEV User
        $incomingTev = AccountingUnit::firstOrCreate(
            ['email' => 'incoming.tev@email.com'],  
            [
                'fname' => 'Incoming',
                'mi' => 'T',
                'lname' => 'EV',
                'password' => 'password',
                'profile_image' => 'default.png',
                'email_verified_at' => now(), // ✅
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
        $incomingTev->assignRole($incomingTevRole);

        // Budget Officers
        $budgetOfficer = AccountingUnit::firstOrCreate(
            ['email' => 'budget.officer@email.com'],
            [
                'fname' => 'Budget',
                'mi' => 'O',
                'lname' => 'Officer',
                'password' => 'password',
                'profile_image' => '52.jpg',
                'email_verified_at' => now(), // ✅
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
                'password' => 'password',
                'profile_image' => 'default.png',
                'email_verified_at' => now(), // ✅
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
                'password' => 'password',
                'profile_image' => 'default.png',
                'email_verified_at' => now(), // ✅
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
        $budgetOfficer3->assignRole($budgetOfficerRole);
        
        // Outgoing Users
        $outgoing = AccountingUnit::firstOrCreate(
            ['email' => 'outgoing@email.com'],
            [
                'fname' => 'Outgoing',
                'mi' => 'D',
                'lname' => 'Going',
                'password' => 'password',
                'profile_image' => '37.jpg',
                'email_verified_at' => now(), // ✅
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
                'password' => 'password',
                'profile_image' => 'default.png',
                'email_verified_at' => now(), // ✅
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
                'password' => 'password',
                'profile_image' => 'default.png',
                'email_verified_at' => now(), // ✅
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
        $outgoing3->assignRole($outgoingRole);

        // Accounting III
        $accounting = AccountingUnit::firstOrCreate(
            ['email' => 'accounting3@email.com'],
            [
                'fname' => 'Accounting',
                'mi' => 'A',
                'lname' => 'Accounting',
                'password' => 'password',
                'profile_image' => '79.jpg',
                'email_verified_at' => now(), // ✅
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
        $accounting->assignRole($accountingRole);

        // Regional Director
        $rd = AccountingUnit::firstOrCreate(
            ['email' => 'regional.director@email.com'],
            [
                'fname' => 'Regional',
                'mi' => 'R',
                'lname' => 'Director',
                'password' => 'password',
                'profile_image' => 'default.png',
                'email_verified_at' => now(), // ✅
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
        $rd->assignRole($regionalDirectorRole);

        // Cashier
        $cash = AccountingUnit::firstOrCreate(
            ['email' => 'cashier@email.com'],
            [
                'fname' => 'Cashier',
                'mi' => 'C',
                'lname' => 'Cashier',
                'password' => 'password',
                'profile_image' => 'default.png',
                'email_verified_at' => now(), // ✅
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
        $cash->assignRole($cashierRole);
    }
}