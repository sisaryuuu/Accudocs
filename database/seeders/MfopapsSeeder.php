<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MfopapsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $mfopaps = [
            ['mfocode' => '100000100001000',   'mfodescription' => 'GENERAL ADMINISTRATION AND SUPPORT SERVICES'],
            ['mfocode' => '310100100002000',   'mfodescription' => 'SPES'],
            ['mfocode' => '310100100003000',   'mfodescription' => 'PUBLIC EMPLOYMENT SERVICES'],
            ['mfocode' => '161003030100000',   'mfodescription' => 'WODP'],
            ['mfocode' => '320100100005000',   'mfodescription' => 'LLCS'],
            ['mfocode' => '320100100006000',   'mfodescription' => 'CASE MANAGEMENT'],
            ['mfocode' => '330100100003000',   'mfodescription' => 'DILP'],
            ['mfocode' => '330100100004000',   'mfodescription' => 'FWP'],
            ['mfocode' => '41306000002',       'mfodescription' => 'COMPUTERIZATION PROGRAM'],
            ['mfocode' => '3020100001.6',      'mfodescription' => 'JOBSTART PHILS'],
            ['mfocode' => '3020100002',        'mfodescription' => 'LMI'],
            ['mfocode' => '3020100002.3',      'mfodescription' => 'JOB FAIR'],
            ['mfocode' => '4130600001',        'mfodescription' => 'NSRS'],
            ['mfocode' => '303010000.2',       'mfodescription' => 'TRIPARTISM AND SOCIAL DIALOGUE'],
            ['mfocode' => '303010000.4',       'mfodescription' => 'LEES'],
            ['mfocode' => '33010010000300.4',  'mfodescription' => 'GIP'],
            ['mfocode' => '33010010000300.4',  'mfodescription' => 'TUPAD'], // ⚠️ same code as GIP
            ['mfocode' => '303020000',         'mfodescription' => 'AMP'],
            ['mfocode' => '303020000.1',       'mfodescription' => 'CLPEP'],
        ];

        DB::table('mfopaps')->insert($mfopaps);
    }
}
