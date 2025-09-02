<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
class ResponCenterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
         $responcenters = [
            ['rccode' => '16-001-03-00011-01-01', 'description' => 'ORD'],
            ['rccode' => '16-001-03-00011-01-02', 'description' => 'OARD'],
            ['rccode' => '16-001-03-00011-01-01-01', 'description' => 'MALSU'],
            ['rccode' => '16-001-03-00011-02-01', 'description' => 'IMSD'],
            ['rccode' => '16-001-03-00011-02-02', 'description' => 'TSSD'],
            ['rccode' => '16-001-03-00011-03-01', 'description' => 'DCFO'],
            ['rccode' => '16-001-03-00011-03-02', 'description' => 'DNFO'],
            ['rccode' => '16-001-03-00011-03-03', 'description' => 'DOFO'],
            ['rccode' => '16-001-03-00011-03-04', 'description' => 'DSFO'],
            ['rccode' => '16-001-03-00011-03-05', 'description' => 'DOCFO'],
            ['rccode' => '16-001-03-00011-03-06', 'description' => 'DORFO'],
            ['rccode' => '16-001-03-00011-03-07', 'description' => 'DIEO'],
            ['rccode' => '16-001-03-00011-03-08', 'description' => 'DBFO'],
        ];

        DB::table('responcenters')->insert($responcenters);
    }
}
