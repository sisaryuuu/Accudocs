<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TEVAttachmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // ✅ AUTO-CLEAN: Delete existing data first
        $this->command->info('🧹 Cleaning existing TEV attachments...');
        $deletedCount = DB::table('tev_attachments')->delete();
        $this->command->info("   ✓ Deleted {$deletedCount} existing attachments");
        $this->command->newLine();
        
        $now = Carbon::now();
        
        $attachments = [
            // ========================================
            // CASH ADVANCE FOR LOCAL TRAVEL - 5 items
            // ========================================
            ['name' => '2 Copies of Obligation Request and Status (ORS)', 'modality' => 'Cash Advance for Local Travel', 'sort_order' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['name' => '3 copies of Disbursement Vouchers (DVs)', 'modality' => 'Cash Advance for Local Travel', 'sort_order' => 2, 'created_at' => $now, 'updated_at' => $now],
            ['name' => '2 copies of Approved Itinerary of Travel', 'modality' => 'Cash Advance for Local Travel', 'sort_order' => 3, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Approved Office Order or Travel Order', 'modality' => 'Cash Advance for Local Travel', 'sort_order' => 4, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Certification from the Accountant that previous cash advance has been liquidated and accounted for in the books', 'modality' => 'Cash Advance for Local Travel', 'sort_order' => 5, 'created_at' => $now, 'updated_at' => $now],

            // ========================================
            // LIQUIDATION OF CASH ADVANCES - 11 items
            // ========================================
            ['name' => '2 copies of Liquidation Report (Appendix 44, GAM)', 'modality' => 'Liquidation of Cash Advances', 'sort_order' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['name' => '2 copies of Revised Itinerary of Travel, if the previously approved itinerary was not followed', 'modality' => 'Liquidation of Cash Advances', 'sort_order' => 2, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Certificate of Appearance or Attendance', 'modality' => 'Liquidation of Cash Advances', 'sort_order' => 3, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Copy of previously approved Itinerary of Travel', 'modality' => 'Liquidation of Cash Advances', 'sort_order' => 4, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Approved Travel Order or Notice of Inspection or Approved Official Business or equivalent', 'modality' => 'Liquidation of Cash Advances', 'sort_order' => 5, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Paper or electronic plane, boat, or bus tickets; boarding pass; terminal fee receipts', 'modality' => 'Liquidation of Cash Advances', 'sort_order' => 6, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Official invoices/receipts (e.g., hotel bills, Grab or taxi receipts)', 'modality' => 'Liquidation of Cash Advances', 'sort_order' => 7, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Certification by the Head of Agency as to the absolute necessity of the expenses, with corresponding bills/receipts', 'modality' => 'Liquidation of Cash Advances', 'sort_order' => 8, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Certificate of Expense Not Requiring Receipts (CENRR) or Reimbursable Expense Receipt (RER), as applicable', 'modality' => 'Liquidation of Cash Advances', 'sort_order' => 9, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Other supporting documents necessary to justify travel and reimbursement', 'modality' => 'Liquidation of Cash Advances', 'sort_order' => 10, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Official Receipt (OR) in case of refund', 'modality' => 'Liquidation of Cash Advances', 'sort_order' => 11, 'created_at' => $now, 'updated_at' => $now],
        ];

        // ✅ STEP 1: Insert Master Templates (dv_request_id = NULL)
        $this->command->info('🔄 Creating TEV attachment master templates...');
        
        foreach ($attachments as $item) {
            DB::table('tev_attachments')->insert(array_merge($item, [
                'dv_request_id' => null, // Master template
            ]));
        }

        // Count by modality
        $counts = DB::table('tev_attachments')
            ->whereNull('dv_request_id')
            ->select('modality', DB::raw('COUNT(*) as count'))
            ->groupBy('modality')
            ->get();

        $this->command->info('✅ Master templates created successfully!');
        $this->command->newLine();
        $this->command->info('📊 Summary:');
        foreach ($counts as $count) {
            $this->command->info("   - {$count->modality}: {$count->count} attachments");
        }
        $this->command->newLine();
        $this->command->info('ℹ️  Attachments will be created on-demand when DV requests are viewed.');
    }
}