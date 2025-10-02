<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
class ADLNumberSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
       $adlcodes = [
            ['adlcode' => '2025-02-0133', 'description' => ''],
            ['adlcode' => '2025-04-0582', 'description' => ''],
            ['adlcode' => '2025-04-0551', 'description' => ''],
            ['adlcode' => '2025-04-0537', 'description' => ''],
            ['adlcode' => '2025-05-0634', 'description' => ''],
            ['adlcode' => '2025-05-0688', 'description' => ''],
            ['adlcode' => '2025-05-0703', 'description' => ''],
            ['adlcode' => '2025-05-0719', 'description' => ''],
            ['adlcode' => '2025-06-0801', 'description' => ''],
            ['adlcode' => '2025-01-0013', 'description' => ''],
            ['adlcode' => '2025-02-0191', 'description' => ''],
            ['adlcode' => '2025-02-0166', 'description' => ''],
            ['adlcode' => '2025-01-0029', 'description' => ''],
            ['adlcode' => '2025-03-0229', 'description' => ''],
            ['adlcode' => '2025-03-0233', 'description' => ''],
            ['adlcode' => '2025-03-0297', 'description' => ''],
            ['adlcode' => '2025-03-0326', 'description' => ''],
            ['adlcode' => '2025-03-0383', 'description' => ''],
            ['adlcode' => '2025-04-0534', 'description' => ''],
            ['adlcode' => '2025-04-0599', 'description' => ''],
            ['adlcode' => '2025-04-0592', 'description' => ''],
            ['adlcode' => '2025-05-0631', 'description' => ''],
            ['adlcode' => '2025-05-0670', 'description' => ''],
            ['adlcode' => '2025-05-0737', 'description' => ''],
            ['adlcode' => '2025-05-0743', 'description' => ''],
            ['adlcode' => '2025-06-0774', 'description' => ''],
            ['adlcode' => '2025-06-0793', 'description' => ''],
            ['adlcode' => '2025-07-894',  'description' => ''],
            ['adlcode' => '2025-08-0929', 'description' => ''],
            ['adlcode' => '2025-09-1015', 'description' => ''],
            ['adlcode' => '2025-09-0996', 'description' => ''],
            ['adlcode' => '2025-01-0080', 'description' => ''],
        ];

        DB::table('adlcodes')->insert($adlcodes);
    }
}
