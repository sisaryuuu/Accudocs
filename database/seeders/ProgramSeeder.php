<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Program;
use Illuminate\Database\Seeder;

class ProgramSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Program::insert([
            [
                'prcode' => 'TUPAD',
                'description' => 'Tulong Panghanapbuhay sa Ating Disadvantaged/Displaced Workers',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'prcode' => 'GIP',
                'description' => 'Government Internship Program',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'prcode' => 'TEV',
                'description' => 'Travel Expenses Voucher',
                'created_at' => now(),
                'updated_at' => now()
            ]
        ]);

    }
}
