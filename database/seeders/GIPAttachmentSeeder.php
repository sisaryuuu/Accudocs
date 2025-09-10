<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\GIPAttachment;

class GIPAttachmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $checklistItems = [
            'EUP Payroll',
            'Payroll',
            'Pacsval',
            'DTR with Accomplishment Report',
            'DTR Log',
            'Profile',
            'Brgy. Certification',
            'TOR/My Grade',
            'Insurance',
            'Birth Certificate',
            'Internship Agreement',
            'Evaluation Form-GIP',
        ];

        // Get all DV requests that belong to GIP programs
        $dvRequestIds = DB::table('dv_requests')
            ->whereIn('program_id', function($query) {
                $query->select('id')
                      ->from('programs')
                      ->where('prcode', 'GIP');
            })
            ->pluck('id');

        foreach ($dvRequestIds as $dvRequestId) {
            foreach ($checklistItems as $item) {
                // Check if attachment already exists
                $exists = DB::table('gip_attachments')
                    ->where('dv_request_id', $dvRequestId)
                    ->where('name', $item)
                    ->exists();

                if (!$exists) {
                    DB::table('gip_attachments')->insert([
                        'dv_request_id' => $dvRequestId,
                        'name'          => $item,
                        'is_present'    => false, // default to not on hand
                        'created_at'    => now(),
                        'updated_at'    => now(),
                    ]);
                }
            }
        }
    }
}
