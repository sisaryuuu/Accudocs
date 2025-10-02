<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AllotmentClassSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
   public function run(): void
    {
        DB::table('allotment_classes')->insert([
            [
                'allotcode'   => '01',
                'description' => 'PERSONNEL SERVICES (PS)',
            ],
            [
                'allotcode'   => '02',
                'description' => 'MAINTENANCE AND OTHER OPERATING EXPENSES (MOOE)',
            ],
            [
                'allotcode'   => '06',
                'description' => 'CAPITAL OUTLAY (CO)',
            ],
        ]);
    }
}
