<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    /*
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            // Focal Person
            'focalperson.view',
            'focalperson.create',
            'focalperson.edit',
            'focalperson.delete',

            // IMSD (Initial Verification)
            
            'imsd.approve',
            'imsd.view',
            'imsd.reject',


            // Incoming
            'tupad.view',
            'tupad.approve',
            'tupad.edit',
            'tupad.reject',

            'gip.view',
            'gip.approve',
            'gip.edit',
            'gip.reject',

            'tev.view',
            'tev.approve',
            'tev.edit',
            'tev.reject',


            // Budget Officer
            'budget.view',
            'budget.approve',
            'budget.edit',
            'budget.onhold',
            'budget.generateors',
            'budget.printors',

            // Outgoing
            'outgoing.view',
            'outgoing.approve',
            'outgoing.edit',
            'outgoing.generatedv',
            'outgoing.printdv',
            'outgoing.reject',

            // Accounting
            'accunit.view',
            'accunit.create',
            'accunit.edit',
            'accunit.delete',
            'viewdataanyltics',
        ];


        foreach ($permissions as $permission)
            {
               Permission::create(['name' => $permission]);
            }
    }
}
