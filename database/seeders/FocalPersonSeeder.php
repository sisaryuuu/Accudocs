<?php


namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\FocalPerson;
use App\Models\Program;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class FocalPersonSeeder extends Seeder
{
    public function run(): void
    {
        // Create the Focal Person role if it doesn't exist
        $focalPersonRole = Role::firstOrCreate(['name' => 'Focal_Person', 'guard_name' => 'focal']);

        // Sample focal persons data
        $focalPersons = [
            [
                'fname' => 'John',
                'mi' => 'D',
                'lname' => 'Smith',
                'TIN_number' => '123-456-789',
                'Address' => '123 Main St, Cityville',
                'email' => 'john.smith@example.com',
                'profile_image' => 'default.png',
                'contactnumber' => '09123456789',
                'password' => ('john123'), // Add hashed password
                'program_id' => Program::first()->id ?? null,
                'respocenter_id' => 1
            ],
            [
                'fname' => 'Maria',
                'mi' => 'R',
                'lname' => 'Garcia',
                'TIN_number' => '987-654-321',
                'Address' => '456 Elm St, Townsville',
                'email' => 'maria.garcia@example.com',
                'profile_image' => 'default.png',
                'contactnumber' => '09123456788',
                'password' =>('maria123'), // Add hashed password
                'program_id' => Program::skip(1)->first()->id ?? null,
                'respocenter_id' => 2
            ],
            [
                'fname' => 'Robert',
                'mi' => 'L',
                'lname' => 'Johnson',
                'TIN_number' => '555-666-777',
                'Address' => '789 Oak St, Villagetown',
                'email' => 'robert.johnson@example.com',
                'profile_image' => 'default.png',
                'contactnumber' => '09123456787',
                 'password' =>('robert123'), // Add hashed password
                'program_id' => Program::skip(2)->first()->id ?? null,
                'respocenter_id' => 3
            ]
        ];

        foreach ($focalPersons as $person) {
            $focalPerson = FocalPerson::create($person);
              if ($focalPersonRole) {
                $focalPerson->assignRole($focalPersonRole);
            }
        }
    }
}