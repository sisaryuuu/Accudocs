<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class DummyDvRequestsSeeder extends Seeder
{
    public function run(): void
    {
        $startMonth = Carbon::create(2024, 9, 1); // Start at September 2024
        $endMonth = Carbon::now(); // Until current month

        $months = [];
        $current = $startMonth->copy();

        // Collect all months
        while ($current <= $endMonth) {
            $months[] = $current->copy();
            $current->addMonth();
        }

        // Weighted program distribution
        $programDistribution = [
            1 => 60, // TUPAD
            2 => 25, // GIP
            3 => 15, // TEV
        ];

        // Weighted random function
        $pickProgram = function () use ($programDistribution) {
            $rand = rand(1, 100);
            $cumulative = 0;

            foreach ($programDistribution as $programId => $weight) {
                $cumulative += $weight;
                if ($rand <= $cumulative) {
                    return $programId;
                }
            }

            return 1; // default fallback
        };

        foreach ($months as $month) {
            $records = [];

            for ($i = 0; $i < 100; $i++) {
                $randomDay = rand(1, $month->daysInMonth);
                $randomDate = $month->copy()->day($randomDay)->addHours(rand(0, 23))->addMinutes(rand(0, 59));

                $records[] = [
                    'reference_number' => 'REF-' . strtoupper(Str::random(10)),
                    'accountingunit_id' => rand(1, 3),
                    'focalperson_id' => rand(1, 3),
                    'program_id' => $pickProgram(),
                    'respoCenter_id' => rand(1, 3),
                    'particulars' => 'Auto-generated DV entry for ' . $month->format('F Y'),
                    'amount' => rand(10000, 90000),
                    'status' => 'submitted',
                    'locked_by' => rand(1, 3),
                    'locked_at' => $randomDate,
                    'created_at' => $randomDate,
                    'updated_at' => $randomDate->copy()->addDays(rand(0, 5)),
                ];
            }

            DB::table('dv_requests')->insert($records);
            $this->command->info('✅ Inserted 100 realistic records for ' . $month->format('F Y'));
        }

        $this->command->info('🎉 All realistic dummy data inserted successfully!');
    }
}
