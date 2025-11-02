<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DummyDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // First, clear existing data safely
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('dv_requests')->truncate();
        DB::table('dv_request_mode_of_payment')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $dummyData = [
            // Focal Person 1 - Sequences: 1, 4, 7
            [
                'reference_number' => 'DVORS-001',
                'dv_number' => null, // Explicitly set to null
                'user_sequence' => 1,
                'accounting_control_number' => 1001,
                'accountingunit_id' => 1,
                'focalperson_id' => 1,
                'program_id' => 1,
                'respocenter_id' => 1,
                'particulars' => 'Payment for Office Supplies',
                'amount' => 1500.00,
                'status' => 'submitted',
                'created_at' => '2025-01-02 08:15:00',
                'updated_at' => '2025-01-02 08:15:00',
            ],
            // Focal Person 2 - Sequences: 1, 5, 8
            [
                'reference_number' => 'DVORS-002',
                'dv_number' => null, // Explicitly set to null
                'user_sequence' => 1,
                'accounting_control_number' => 1002,
                'accountingunit_id' => 1,
                'focalperson_id' => 2,
                'program_id' => 2,
                'respocenter_id' => 1,
                'particulars' => 'Travel Expense Reimbursement',
                'amount' => 2500.00,
                'status' => 'submitted',
                'created_at' => '2025-01-05 14:20:00',
                'updated_at' => '2025-01-05 14:20:00',
            ],
            // Focal Person 3 - Sequences: 1, 6, 9
            [
                'reference_number' => 'DVORS-003',
                'dv_number' => null, // Explicitly set to null
                'user_sequence' => 1,
                'accounting_control_number' => 1003,
                'accountingunit_id' => 1,
                'focalperson_id' => 3,
                'program_id' => 3,
                'respocenter_id' => 1,
                'particulars' => 'Equipment Maintenance',
                'amount' => 1800.00,
                'status' => 'submitted',
                'created_at' => '2025-01-08 09:45:00',
                'updated_at' => '2025-01-08 09:45:00',
            ],
            // Focal Person 1 - Second request
            [
                'reference_number' => 'DVORS-004',
                'dv_number' => null, // Explicitly set to null
                'user_sequence' => 2,
                'accounting_control_number' => 1004,
                'accountingunit_id' => 1,
                'focalperson_id' => 1,
                'program_id' => 2,
                'respocenter_id' => 1,
                'particulars' => 'Software License Renewal',
                'amount' => 3200.00,
                'status' => 'submitted',
                'created_at' => '2025-01-12 16:30:00',
                'updated_at' => '2025-01-12 16:30:00',
            ],
            // Focal Person 2 - Second request
            [
                'reference_number' => 'DVORS-005',
                'dv_number' => null, // Explicitly set to null
                'user_sequence' => 2,
                'accounting_control_number' => 1005,
                'accountingunit_id' => 1,
                'focalperson_id' => 2,
                'program_id' => 3,
                'respocenter_id' => 1,
                'particulars' => 'Training Materials',
                'amount' => 2750.00,
                'status' => 'submitted',
                'created_at' => '2025-01-15 11:10:00',
                'updated_at' => '2025-01-15 11:10:00',
            ],
            // Focal Person 3 - Second request
            [
                'reference_number' => 'DVORS-006',
                'dv_number' => null, // Explicitly set to null
                'user_sequence' => 2,
                'accounting_control_number' => 1006,
                'accountingunit_id' => 1,
                'focalperson_id' => 3,
                'program_id' => 1,
                'respocenter_id' => 1,
                'particulars' => 'Internet Services',
                'amount' => 4200.00,
                'status' => 'submitted',
                'created_at' => '2025-01-18 07:45:00',
                'updated_at' => '2025-01-18 07:45:00',
            ],
            // Focal Person 1 - Third request
            [
                'reference_number' => 'DVORS-007',
                'dv_number' => null, // Explicitly set to null
                'user_sequence' => 3,
                'accounting_control_number' => 1007,
                'accountingunit_id' => 1,
                'focalperson_id' => 1,
                'program_id' => 3,
                'respocenter_id' => 1,
                'particulars' => 'Office Rent',
                'amount' => 5800.00,
                'status' => 'submitted',
                'created_at' => '2025-01-20 10:20:00',
                'updated_at' => '2025-01-20 10:20:00',
            ],
            // Focal Person 2 - Third request
            [
                'reference_number' => 'DVORS-008',
                'dv_number' => null, // Explicitly set to null
                'user_sequence' => 3,
                'accounting_control_number' => 1008,
                'accountingunit_id' => 1,
                'focalperson_id' => 2,
                'program_id' => 1,
                'respocenter_id' => 1,
                'particulars' => 'Utility Bills',
                'amount' => 1950.00,
                'status' => 'submitted',
                'created_at' => '2025-01-23 14:55:00',
                'updated_at' => '2025-01-23 14:55:00',
            ],
            // Focal Person 3 - Third request
            [
                'reference_number' => 'DVORS-009',
                'dv_number' => null, // Explicitly set to null
                'user_sequence' => 3,
                'accounting_control_number' => 1009,
                'accountingunit_id' => 1,
                'focalperson_id' => 3,
                'program_id' => 2,
                'respocenter_id' => 1,
                'particulars' => 'Marketing Materials',
                'amount' => 3100.00,
                'status' => 'submitted',
                'created_at' => '2025-01-25 16:40:00',
                'updated_at' => '2025-01-25 16:40:00',
            ],
            // Focal Person 1 - Fourth request
            [
                'reference_number' => 'DVORS-010',
                'dv_number' => null, // Explicitly set to null
                'user_sequence' => 4,
                'accounting_control_number' => 1010,
                'accountingunit_id' => 1,
                'focalperson_id' => 1,
                'program_id' => 3,
                'respocenter_id' => 1,
                'particulars' => 'Consultancy Fees',
                'amount' => 7500.00,
                'status' => 'submitted',
                'created_at' => '2025-01-28 13:25:00',
                'updated_at' => '2025-01-28 13:25:00',
            ],
        ];

        // Insert the data
        DB::table('dv_requests')->insert($dummyData);

        // Mode of Payment Pivot Data
        $modeOfPaymentData = [
            ['dv_request_id' => 1, 'mode_of_payment_id' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['dv_request_id' => 2, 'mode_of_payment_id' => 2, 'created_at' => now(), 'updated_at' => now()],
            ['dv_request_id' => 3, 'mode_of_payment_id' => 3, 'created_at' => now(), 'updated_at' => now()],
            ['dv_request_id' => 4, 'mode_of_payment_id' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['dv_request_id' => 5, 'mode_of_payment_id' => 2, 'created_at' => now(), 'updated_at' => now()],
            ['dv_request_id' => 6, 'mode_of_payment_id' => 3, 'created_at' => now(), 'updated_at' => now()],
            ['dv_request_id' => 7, 'mode_of_payment_id' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['dv_request_id' => 8, 'mode_of_payment_id' => 2, 'created_at' => now(), 'updated_at' => now()],
            ['dv_request_id' => 9, 'mode_of_payment_id' => 3, 'created_at' => now(), 'updated_at' => now()],
            ['dv_request_id' => 10, 'mode_of_payment_id' => 1, 'created_at' => now(), 'updated_at' => now()],
        ];

        DB::table('dv_request_mode_of_payment')->insert($modeOfPaymentData);
        
        $this->command->info('Dummy data seeded successfully with dv_number as null!');
    }
}