<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ModeOfPayment;

class ModeOfPaymentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $modes = [
            ['mopname' => 'MDS Check'],
            ['mopname' => 'Commercial Check'],
            ['mopname' => 'ADA'],
        ];

        foreach ($modes as $mode) {
            ModeOfPayment::firstOrCreate($mode);
        }
    }
}
