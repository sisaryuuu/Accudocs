<?php

namespace App\Traits;

trait HandlesDepartmentTransitions
{
    /**
     * Update department timestamps when status changes
     * This handles ALL department transitions
     */
    protected function updateDepartmentTimestamp($dvRequest, $newStatus)
    {
        // Map of statuses to their corresponding timestamp fields
        // Now each status can set MULTIPLE fields
        $timestampMap = [
            // ⭐ IMSD DEPARTMENT
            'submitted' => ['created_at'],
            'IMSD_received' => ['imsd_received_at'],
            'IMSD_verified' => ['imsd_verified_at', 'imsd_last_action_at'], // ⭐ BOTH!
        
            
            // ⭐ GIP DEPARTMENT
            'Incoming-GIP_received' => ['gip_received_at'], // ⭐ Resets IMSD→GIP clock
            'Incoming-GIP_verified' => ['gip_verified_at', 'gip_last_action_at'], // ⭐ BOTH!
        
            
            // ⭐ TUPAD DEPARTMENT
            'Incoming-TUPAD_received' => ['tupad_received_at'], // ⭐ Resets IMSD→TUPAD clock
            'Incoming-TUPAD_verified' => ['tupad_verified_at', 'tupad_last_action_at'], // ⭐ BOTH!
          
            
            // ⭐ TEV DEPARTMENT
            'Incoming-TEV_received' => ['tev_received_at'], // ⭐ Resets IMSD→TEV clock
            'Incoming-TEV_verified' => ['tev_verified_at', 'tev_last_action_at'], // ⭐ BOTH!
    
            
            // ⭐ BUDGET DEPARTMENT
            'ORS_received' => ['budget_received_at'], // ⭐ Resets GIP/TUPAD/TEV→Budget clock
            'ORS_obligated' => ['budget_verified_at', 'budget_last_action_at'], // ⭐ BOTH!
           
            
            // ⭐ ACCOUNTANT DEPARTMENT
            'RANCA_received' => ['accountant_received_at'], // ⭐ Resets Budget→Accountant clock
            'RANCA_validated' => ['accountant_verified_at', 'accountant_last_action_at'], // ⭐ BOTH!
            
            
            // ⭐ OUTGOING DEPARTMENT
            'Outgoing_received' => ['outgoing_received_at'], // ⭐ Resets Accountant→Outgoing clock
            'Outgoing_finalized' => ['outgoing_verified_at', 'outgoing_last_action_at'], // ⭐ BOTH!

            
            // ⭐ REJECTIONS
            'IMSD_rejected' => ['last_action_at'],
            'Incoming-GIP_rejected' => ['last_action_at'],
            'Incoming-TUPAD_rejected' => ['last_action_at'],
            'Incoming-TEV_rejected' => ['last_action_at'],
        ];
        
        $fields = $timestampMap[$newStatus] ?? [];
        $updated = false;
        
        foreach ($fields as $field) {
            // ⭐ CRITICAL RULES:
            
            // 1. For _received_at fields: ONLY set once when first received
            // This is what "resets the transition clock"
            if (str_contains($field, '_received_at') && is_null($dvRequest->$field)) {
                $dvRequest->$field = now();
                $updated = true;
            }
            
            // 2. For _verified_at fields: Set when verified
            elseif (str_contains($field, '_verified_at') && is_null($dvRequest->$field)) {
                $dvRequest->$field = now();
                $updated = true;
            }
            
            // 3. For _last_action_at fields: Update on any action
            elseif (str_contains($field, '_last_action_at')) {
                $dvRequest->$field = now();
                $updated = true;
            }
            
            // 4. For created_at (only for 'submitted')
            elseif ($field === 'created_at' && is_null($dvRequest->$field)) {
                $dvRequest->$field = now();
                $updated = true;
            }
        }
        
        // Always update the general tracking
        $dvRequest->last_action_at = now();
        $dvRequest->last_action_type = 'status_change_to_' . $newStatus;
        $dvRequest->last_acted_by = auth()->id();
        $dvRequest->save();
        
        return $fields;
    }
    
    /**
     * Get the base date for aging calculations
     */
    protected function getAgingBaseDate($dvRequest, $department)
    {
        $dateFieldMap = [
            'imsd' => 'imsd_received_at',
            'gip' => 'gip_received_at',
            'tupad' => 'tupad_received_at',
            'tev' => 'tev_received_at',
            'budget' => 'budget_received_at',
            'accountant' => 'accountant_received_at',
            'outgoing' => 'outgoing_received_at',
        ];
        
        $field = $dateFieldMap[$department] ?? null;
        
        if ($field && $dvRequest->$field) {
            return $dvRequest->$field;
        }
        
        return $dvRequest->created_at;
    }
    
    /**
     * Fix existing records with missing timestamps
     */
    public function fixMissingTimestamps($dvRequest)
    {
        // Based on current status, set appropriate timestamps
        switch ($dvRequest->status) {
            case 'IMSD_verified':
                if (is_null($dvRequest->imsd_verified_at) && $dvRequest->last_action_at) {
                    $dvRequest->imsd_verified_at = $dvRequest->last_action_at;
                    $dvRequest->imsd_last_action_at = $dvRequest->last_action_at;
                    $dvRequest->save();
                }
                break;
                
            case 'Incoming-GIP_received':
                if (is_null($dvRequest->gip_received_at) && $dvRequest->last_action_at) {
                    $dvRequest->gip_received_at = $dvRequest->last_action_at;
                    $dvRequest->save();
                }
                break;
                
            case 'Incoming-GIP_verified':
                if (is_null($dvRequest->gip_verified_at) && $dvRequest->last_action_at) {
                    $dvRequest->gip_verified_at = $dvRequest->last_action_at;
                    $dvRequest->gip_last_action_at = $dvRequest->last_action_at;
                    $dvRequest->save();
                }
                break;
                
            // Add other statuses as needed
        }
        
        return $dvRequest;
    }
}