<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
class TUPADAttachmentSeeder extends Seeder
{
     /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = Carbon::now();
        
        $attachments = [
            // ========================================
            // RPMT (Direct Administration)
            // ========================================
            [
                'name' => 'RPMT',
                'modality' => 'rpmt',
                'sort_order' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'TUPAD Work Program (Annex B)',
                'modality' => 'rpmt',
                'sort_order' => 2,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Profile of TUPAD Beneficiaries (Annex D)',
                'modality' => 'rpmt',
                'sort_order' => 3,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Contract of Service (Annex E-2)',
                'modality' => 'rpmt',
                'sort_order' => 4,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Payroll',
                'modality' => 'rpmt',
                'sort_order' => 5,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Notice to Proceed',
                'modality' => 'rpmt',
                'sort_order' => 6,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Photocopy of Valid IDs',
                'modality' => 'rpmt',
                'sort_order' => 7,
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // ========================================
            // LOCAL GOVERNMENT UNIT (LGU)
            // ========================================
            [
                'name' => 'Letter of Intent (Annex F)',
                'modality' => 'lgu',
                'sort_order' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'RPMT',
                'modality' => 'lgu',
                'sort_order' => 2,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'TUPAD Work Program (Annex B)',
                'modality' => 'lgu',
                'sort_order' => 3,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Profile of TUPAD Beneficiaries (Annex D)',
                'modality' => 'lgu',
                'sort_order' => 4,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Contract of Service (Annex E-2)',
                'modality' => 'lgu',
                'sort_order' => 5,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Notice to Proceed',
             
                'modality' => 'lgu',
                'sort_order' => 6,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Photocopy of Valid IDs',
             
                'modality' => 'lgu',
                'sort_order' => 7,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Local Development Plan',
                'modality' => 'lgu',
                'sort_order' => 8,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Sangguniang Bayan (SB) or Board Resolution',
                'modality' => 'lgu',
                'sort_order' => 9,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Memorandum of Agreement (Annex G)',
                'modality' => 'lgu',
                'sort_order' => 10,
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // ========================================
            // NON-GOVERNMENT ORGANIZATIONS (NGO) / PEOPLE'S ORGANIZATIONS (POs)
            // ========================================
            [
                'name' => 'Letter of Intent (Annex F)',
                'modality' => 'ngo_po',
                'sort_order' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
             [
                'name' => 'RPMT',
                'modality' => 'ngo_po',
                'sort_order' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'TUPAD Work Program (Annex B)',
                'modality' => 'ngo_po',
                'sort_order' => 2,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Profile of TUPAD Beneficiaries (Annex D)',
                'modality' => 'ngo_po',
                'sort_order' => 3,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Contract of Service (Annex E-2)',
                'modality' => 'ngo_po',
                'sort_order' => 4,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Notice to Proceed',
             
                'modality' => 'ngo_po',
                'sort_order' => 5,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Photocopy of Valid IDs',
             
                'modality' => 'ngo_po',
                'sort_order' => 6,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Local Development Plan',
               
                'modality' => 'ngo_po',
                'sort_order' => 7,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Sangguniang Bayan (SB) or Board Resolution',
                'modality' => 'ngo_po',
                'sort_order' => 8,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Memorandum of Agreement (Annex G)',
                
                'modality' => 'ngo_po',
                'sort_order' => 9,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Audited Financial Reports for the past three (3) years',
                
                'modality' => 'ngo_po',
                'sort_order' => 10,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Work and Financial Plan',
            
                'modality' => 'ngo_po',
                'sort_order' => 11,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'List of photographs of similar implemented projects', 
                'modality' => 'ngo_po',
                'sort_order' => 12,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Sworn Affidavit',
                'modality' => 'ngo_po',
                'sort_order' => 13,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Copy of Accredited as Co-Partner (ACP) Certificate',
              
                'modality' => 'ngo_po',
                'sort_order' => 14,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Constitution and By-Laws Authenticated (if registered with DOLE)',
                'modality' => 'ngo_po',
                'sort_order' => 15,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Updated list of officers and members (if registered with DOLE)',
             
                'modality' => 'ngo_po',
                'sort_order' => 16,
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // ========================================
            // MONEY REMITTANCE
            // ========================================
            [
                'name' => 'RPMT',
                'modality' => 'money_remittance',
                'sort_order' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'TUPAD Work Program (Annex B)',
                'modality' => 'money_remittance',
                'sort_order' => 2,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Profile of TUPAD Beneficiaries (Annex D)',
                'modality' => 'money_remittance',
                'sort_order' => 3,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Contract of Service (Annex E-2)',
                'modality' => 'money_remittance',
                'sort_order' => 4,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'TUPAD Payroll',
                'modality' => 'money_remittance',
                'sort_order' => 5,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Money Remittance Payroll',
            
                'modality' => 'money_remittance',
                'sort_order' => 6,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Notice to Proceed',
             
                'modality' => 'money_remittance',
                'sort_order' => 7,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Photocopy of Valid IDs',
                'modality' => 'money_remittance',
                'sort_order' => 8,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Daily Time Records (DTRs) / Attendance Sheets',
                'modality' => 'money_remittance',
                'sort_order' => 9,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'TUPAD Completion Report',
                'modality' => 'money_remittance',
                'sort_order' => 10,
                'created_at' => $now,
                'updated_at' => $now,
            ],
              [
                'name' => 'Letter Of Intent (Annex F)',
                'modality' => 'individual_beneficiaries',
                'sort_order' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
              [
                'name' => 'RPMT',
                'modality' => 'individual_beneficiaries',
                'sort_order' => 2,
                'created_at' => $now,
                'updated_at' => $now,
            ],
              [
                'name' => 'Business Plan',
                'modality' => 'individual_beneficiaries',
                'sort_order' => 3,
                'created_at' => $now,
                'updated_at' => $now,
            ],
              [
                'name' => 'Beneficiary Profile Form (Annex O)',
                'modality' => 'individual_beneficiaries',
                'sort_order' => 4,
                'created_at' => $now,
                'updated_at' => $now,
            ],
              [
                'name' => 'Photocopy of Valid IDs',
                'modality' => 'individual_beneficiaries',
                'sort_order' => 5,
                'created_at' => $now,
                'updated_at' => $now,
            ],
              [
                'name' => 'Proof of Project Readiness',
                'modality' => 'individual_beneficiaries',
                'sort_order' => 6,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'RPMT',
                'modality' => 'group_beneficiaries',
                'sort_order' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Beneficiary Profile Form (Annex O)',
                'modality' => 'group_beneficiaries',
                'sort_order' => 2,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Notice to Proceed',
                'modality' => 'group_beneficiaries',
                'sort_order' => 3,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Project Proposal',
                'modality' => 'group_beneficiaries',
                'sort_order' => 4,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Local Development Plan',
                'modality' => 'group_beneficiaries',
                'sort_order' => 5,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Sangguiniang Bayan (SB) or Board Resolution',
                'modality' => 'group_beneficiaries',
                'sort_order' => 6,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Memorandum of Agreement (Annex G)',
                'modality' => 'group_beneficiaries',
                'sort_order' => 7,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Audited Financial Reports for the past three (3) years',
                'modality' => 'group_beneficiaries',
                'sort_order' => 8,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Work and Financial Plan',
                'modality' => 'group_beneficiaries',
                'sort_order' => 9,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'List or photographs of similar implemented projects',
                'modality' => 'group_beneficiaries',
                'sort_order' => 10,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Sworn Affidavit',
                'modality' => 'group_beneficiaries',
                'sort_order' => 11,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Copy of Accredited as Co-Partner (ACP) Certificate',
                'modality' => 'group_beneficiaries',
                'sort_order' => 12,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Constitution and By-Laws Authenticated (if registered from DOLE)',
                'modality' => 'group_beneficiaries',
                'sort_order' => 13,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Updated list of officers and members (if registered from DOLE)',
                'modality' => 'group_beneficiaries',
                'sort_order' => 14,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Masterlist',
                'modality' => 'group_beneficiaries',
                'sort_order' => 15,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Photocopy of Valid IDs',
                'modality' => 'group_beneficiaries',
                'sort_order' => 16,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];
       foreach ($attachments as $item) {
                DB::table('tupad_attachments')->updateOrInsert(
                    [
                        'name'     => $item['name'],
                        'modality' => $item['modality'],
                        'dv_request_id' => null, // mark as master template
                    ],
                    $item
                );
            }

        $dvRequestIds = DB::table('dv_requests')
            ->whereIn('program_id', function($query) {
                $query->select('id')
                    ->from('programs')
                    ->where('prcode', 'TUPAD'); 
            })
            ->pluck('id');

        // Get all checklist items (group by modality)
        $checklistItems = DB::table('tupad_attachments')
        ->whereNull('dv_request_id') // only master items
        ->select('name', 'modality', 'sort_order')
        ->get();

        foreach ($dvRequestIds as $dvRequestId) {
            foreach ($checklistItems as $item) {
                $exists = DB::table('tupad_attachments')
                    ->where('dv_request_id', $dvRequestId)
                    ->where('name', $item->name)
                    ->exists();

                if (!$exists) {
                    DB::table('tupad_attachments')->insert([
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