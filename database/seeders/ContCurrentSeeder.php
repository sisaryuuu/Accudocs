<?php

namespace Database\Seeders;


use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ContCurrentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
 public function run(): void
    {
        $contcurrents = [
            [
                'ccode'       => '101101',
                'description' => 'CURRENT APPROPRIATIONS',
            ],
            [
                'ccode'       => '102101',
                'description' => 'CONTINUING APPROPRIATIONS',
            ],
        ];

        DB::table('contcurrents')->insert($contcurrents);
    }

}
