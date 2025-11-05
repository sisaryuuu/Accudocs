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
        $startMonth = Carbon::create(2024, 9, 1);
        $endMonth = Carbon::now();

        $months = [];
        $current = $startMonth->copy();

        while ($current <= $endMonth) {
            $months[] = $current->copy();
            $current->addMonth();
        }

        $programDistribution = [1 => 60, 2 => 25, 3 => 15];
        $statusDistribution = [
            'Incoming-GIP_verified' => 20,
            'Incoming-TUPAD_verified' => 30,
            'Incoming-TEV_verified' => 15,
            'IMSD_verified' => 25,
            'submitted' => 10,
        ];

        $pickProgram = function () use ($programDistribution) {
            $rand = rand(1, 100);
            $cumulative = 0;
            foreach ($programDistribution as $programId => $weight) {
                $cumulative += $weight;
                if ($rand <= $cumulative) return $programId;
            }
            return 1;
        };

        $pickStatus = function () use ($statusDistribution) {
            $rand = rand(1, 100);
            $cumulative = 0;
            foreach ($statusDistribution as $status => $weight) {
                $cumulative += $weight;
                if ($rand <= $cumulative) return $status;
            }
            return 'submitted';
        };

        // Track sequential numbers per accounting unit per year
        $yearlyCounters = [];
        
        // Track user sequences PER FOCAL PERSON (moved outside the month loop)
        $sequenceTracker = [];

        foreach ($months as $month) {
            $records = [];

            for ($i = 0; $i < 100; $i++) {
                $focalpersonId = rand(1, 3);
                $accountingunitId = rand(1, 3);
                $randomDay = rand(1, $month->daysInMonth);
                $randomDate = $month->copy()->day($randomDay)->addHours(rand(0, 23))->addMinutes(rand(0, 59));

                // Initialize sequence tracker for this focal person if not exists
                if (!isset($sequenceTracker[$focalpersonId])) {
                    $sequenceTracker[$focalpersonId] = 1;
                } else {
                    $sequenceTracker[$focalpersonId]++;
                }

                // Initialize counter for this accounting unit for the current year
                $currentYear = $randomDate->year;
                $counterKey = $currentYear . '_' . $accountingunitId;
                
                if (!isset($yearlyCounters[$counterKey])) {
                    $yearlyCounters[$counterKey] = 1;
                }

                // AUTO-GENERATE accounting_control_number with 4 digits
                $accountingControlNumber = 'CN #-' . $currentYear . '-' . str_pad($yearlyCounters[$counterKey], 4, '0', STR_PAD_LEFT);
                
                // Increment the counter
                $yearlyCounters[$counterKey]++;

                $records[] = [
                    'reference_number' => 'DVORS-' . strtoupper(Str::random(10)),
                    'accounting_control_number' => $accountingControlNumber,
                    'accountingunit_id' => $accountingunitId,
                    'focalperson_id' => $focalpersonId,
                    'program_id' => $pickProgram(),
                    'respoCenter_id' => rand(1, 3),
                    'particulars' => 'Auto-generated DV entry for ' . $month->format('F Y'),
                    'amount' => rand(10000, 90000),
                    'status' => $pickStatus(),
                    'locked_by' => rand(1, 3),
                    'locked_at' => $randomDate,
                    'user_sequence' => $sequenceTracker[$focalpersonId], // This is now unique per focal person
                    'created_at' => $randomDate,
                    'updated_at' => $randomDate->copy()->addDays(rand(0, 5)),
                ];
            }

            DB::table('dv_requests')->insert($records);
            $this->command->info('✅ Inserted 100 records for ' . $month->format('F Y'));
        }

        $this->command->info('🎉 All dummy data inserted successfully!');
        
        // Display sample data for verification
        $this->command->info('Sample Accounting Control Numbers (4-digit format):');
        $samples = DB::table('dv_requests')
            ->select('focalperson_id', 'user_sequence', 'accounting_control_number', 'status')
            ->orderBy('accounting_control_number')
            ->limit(15)
            ->get();
            
        foreach ($samples as $sample) {
            $this->command->info("  - ACN: {$sample->accounting_control_number}, Focal: {$sample->focalperson_id}, Seq: {$sample->user_sequence}");
        }
    }
}