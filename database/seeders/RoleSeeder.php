<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Roles with their specific permissions and guard_name
        $rolesWithPermissions = [
            'Admin' => [
                'guard' => 'web',
                'permissions' => Permission::all()->pluck('name')->toArray(), // Admin = ALL
            ],

            'IMSD' => [
                'guard' => 'web',
                'permissions' => [
                    'imsd.view',
                    'imsd.verify',
                    'imsd.reject',
                ],
            ],

            'MALSU' => [
                'guard' => 'web',
                'permissions' => [
                    'malsu.view',
                    'malsu.tev.verify',
                    'malsu.misc.verify',
                ],
            ],

            'TSSD' => [
                'guard' => 'web',
                'permissions' => [
                    'tssd.view',
                    'tssd.tupad.verify',
                    'tssd.gip.verify',
                    'tssd.tev.verify',
                ],
            ],

            'Incoming-TUPAD' => [
                'guard' => 'web',
                'permissions' => [
                    'tupad.view',
                    'tupad.approve',
                    'tupad.edit',
                    'tupad.reject',
                ],
            ],

            'Incoming-GIP' => [
                'guard' => 'web',
                'permissions' => [
                    'gip.view',
                    'gip.approve',
                    'gip.edit',
                    'gip.reject',
                ],
            ],

            'Incoming-TEV' => [
                'guard' => 'web',
                'permissions' => [
                    'tev.view',
                    'tev.approve',
                    'tev.edit',
                    'tev.reject'
                ],
            ],

            'Budget_Officer' => [
                'guard' => 'web',
                'permissions' => [
                    'budget.view',
                    'budget.approve',
                    'budget.edit',
                    'budget.onhold',
                    'budget.generateors',
                    'budget.printors'
                ],
            ],

            'Outgoing' => [
                'guard' => 'web',
                'permissions' => [
                    'outgoing.view',
                    'outgoing.approve',
                    'outgoing.edit',
                    'outgoing.generatedv',
                    'outgoing.rejected',
                    'outgoing.printdv'
                ],
            ],

            'Accounting_III' => [
                'guard' => 'web',
                'permissions' => [ 
                    'accunit.view',
                    'accunit.create',
                    'accunit.edit',
                    'accunit.validate',
                    'accunit.delete',
                    'viewdataanyltics',
                ],
            ],

            // 👇 Notice this one uses "focal" guard
            'Focal_Person' => [
                'guard' => 'focal',
                'permissions' => [
                    'focalperson.view',
                    'focalperson.create',
                    'focalperson.edit',
                    'focalperson.delete',
                ],
            ],
        ];

        foreach ($rolesWithPermissions as $roleName => $data) {
            $guard = $data['guard'];
            $permissions = $data['permissions'];

            // Create role with guard
            $role = Role::firstOrCreate(
                ['name' => $roleName, 'guard_name' => $guard]
            );

            // Make sure each permission exists with correct guard
            foreach ($permissions as $perm) {
                Permission::firstOrCreate([
                    'name' => $perm,
                    'guard_name' => $guard
                ]);
            }

            // Assign permissions
            if ($roleName === 'Admin') {
                $role->syncPermissions(Permission::where('guard_name', $guard)->get());
            } else {
                $role->syncPermissions($permissions);
            }
        }
    }
}
