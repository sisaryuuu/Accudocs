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
                'email' => 'c.mulato.517213@umindanao.edu.ph',
                'profile_image' => 'default.png',
                'contactnumber' => '09123456789',
                'password' => ('password'), // Add hashed password
                'respocenter_id' => 1
            ],
            [
                'fname' => 'Robert',
                'mi' => 'L',
                'lname' => 'Johnson',
                'TIN_number' => '555-666-777',
                'Address' => '789 Oak St, Villagetown',
                'email' => 'jellycasm@gmail.com',
                'profile_image' => 'default.png',
                'contactnumber' => '09123456787',
                 'password' =>('password'), // Add hashed password
                'respocenter_id' => 3
            ],
            [
                'fname' => 'Caesar Alexis',
                'mi' => 'D',
                'lname' => 'Mulato',
                'TIN_number' => '555-666-999',
                'Address' => '789 Oak St, Villagetown',
                'email' => 'rhymeblind31@gmail.com',
                'email_verified_at' => now(),              
                'profile_image' => 'default.png',
                'contactnumber' => '09676767676',
                 'password' =>('password'), // Add hashed password
                'respocenter_id' => 3
            ],
        ];

        foreach ($focalPersons as $person) {
            $focalPerson = FocalPerson::create($person);
              if ($focalPersonRole) {
                $focalPerson->assignRole($focalPersonRole);
            }
        }
    }
}