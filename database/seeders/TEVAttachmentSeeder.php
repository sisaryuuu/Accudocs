<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
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
        $now = Carbon::now();
        
        $attachments = [
            // ========================================
            // CASH ADVANCE FOR LOCAL TRAVEL
            // ========================================
            [
                'name' => '2 Copies of Obligation Request and Status (ORS)',
                'modality' => 'Cash Advance for Local Travel',
                'sort_order' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => '3 copies of Disbursement Vouchers (DVs) ',
                'modality' => 'Cash Advance for Local Travel',
                'sort_order' => 2,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => '2 copies of Approved Itinerary of Travel',
                'modality' => 'Cash Advance for Local Travel',
                'sort_order' => 3,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Approved Office Order or Travel Order',
                'modality' => 'Cash Advance for Local Travel',
                'sort_order' => 4,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Certification from the Accountant that previous cash advance has been liquidated and accounted for in the books',
                'modality' => 'Cash Advance for Local Travel',
                'sort_order' => 5,
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // ========================================
            // LIQUIDATION OF CASH ADVANCES
            // ========================================
            [
                'name' => '2 copies of Liquidation Report (Appendix 44, GAM)',
                'modality' => 'Liquidation of Cash Advances',
                'sort_order' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => '2 copies of Revised Itinerary of Travel, if the previously approved itinerary was not followed',
                'modality' => 'Liquidation of Cash Advances',
                'sort_order' => 2,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Certificate of Appearance or Attendance',
                'modality' => 'Liquidation of Cash Advances',
                'sort_order' => 3,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Copy of previously approved Itinerary of Travel',
                'modality' => 'Liquidation of Cash Advances',
                'sort_order' => 4,
                'created_at' => $now,
                'updated_at' => $now,
            ],
              [
                'name' => 'Approved Travel Order or Notice of Inspection or Approved Official Business or equivalent',
                'modality' => 'Liquidation of Cash Advances',
                'sort_order' => 5,
                'created_at' => $now,
                'updated_at' => $now,
            ],
              [
                'name' => 'Paper or electronic plane, boat, or bus tickets; boarding pass; terminal fee receipts',
                'modality' => 'Liquidation of Cash Advances',
                'sort_order' => 6,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Official invoices/receipts (e.g., hotel bills, Grab or taxi receipts)',
                'modality' => 'Liquidation of Cash Advances',
                'sort_order' => 7,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Certification by the Head of Agency as to the absolute necessity of the expenses, with corresponding bills/receipts',
                'modality' => 'Liquidation of Cash Advances',
                'sort_order' => 8,
                'created_at' => $now,
                'updated_at' => $now,
            ],
             [
                'name' => 'Certificate of Expense Not Requiring Receipts (CENRR) or Reimbursable Expense Receipt (RER), as applicable (per Memorandum No. ROXI-2025-August-603 dated August 1, 2025)',
                'modality' => 'Liquidation of Cash Advances',
                'sort_order' => 9,
                'created_at' => $now,
                'updated_at' => $now,
            ],
             [
                'name' => 'Other supporting documents necessary to justify travel and reimbursement',
                'modality' => 'Liquidation of Cash Advances',
                'sort_order' => 10,
                'created_at' => $now,
                'updated_at' => $now,
            ],
             [
                'name' => 'Official Receipt (OR) in case of refund',
                'modality' => 'Liquidation of Cash Advances',
                'sort_order' => 11,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        // Insert or update master templates (without dv_request_id)
        foreach ($attachments as $item) {
            DB::table('tev_attachments')->updateOrInsert(
                [
                    'name'     => $item['name'],
                    'modality' => $item['modality'],
                    'dv_request_id' => null, // mark as master template
                ],
                $item
            );
        }

        // Get all DV request IDs for TEV program
        $dvRequestIds = DB::table('dv_requests')
            ->whereIn('program_id', function($query) {
                $query->select('id')
                    ->from('programs')
                    ->where('prcode', 'TEV'); 
            })
            ->pluck('id');

        // Get all checklist items (master templates)
        $checklistItems = DB::table('tev_attachments')
            ->whereNull('dv_request_id') // only master items
            ->select('name', 'modality', 'sort_order')
            ->get();

        // Create attachment records for each existing DV request
        foreach ($dvRequestIds as $dvRequestId) {
            foreach ($checklistItems as $item) {
                $exists = DB::table('tev_attachments')
                    ->where('dv_request_id', $dvRequestId)
                    ->where('name', $item->name)    
                    ->exists();

                if (!$exists) {
                    DB::table('tev_attachments')->insert([
                        'dv_request_id' => $dvRequestId,
                        'name'          => $item->name,
                        'modality'      => $item->modality,
                        'is_present'    => false, // default not yet uploaded
                        'sort_order'    => $item->sort_order,
                        'created_at'    => now(),
                        'updated_at'    => now(),
                    ]);
                }
            }
        }
    }
}