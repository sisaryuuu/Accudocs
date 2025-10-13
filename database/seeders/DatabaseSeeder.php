<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
         $this->call([
            PermissionSeeder::class,      // Run first for permissions
            RoleSeeder::class,            // Then create roles
            ProgramSeeder::class,         // Create programs
            ResponCenterSeeder::class,     // Create respo centers
            AccountingUnitSeeder::class,  // Create accounting units
            FocalPersonSeeder::class,   // Finally create focal persons
            ModeOfPaymentSeeder::class,
            MfopapsSeeder::class,
            ContCurrentSeeder::class,
            AllotmentClassSeeder::class,
            ADLNumberSeeder::class,
            TUPADAttachmentSeeder::class,
            TEVAttachmentSeeder::class,
            UACSCodeSeeder::class,   // Seed UACS codes
        ]);
    }
}
