// app/Observers/DvRequestObserver.php
class DvRequestObserver
{
    // Map statuses to department action fields
    protected $statusToDepartmentActionField = [
        'IMSD_verified' => null,
        'Incoming-GIP_received' => 'gip_last_action_at',
        'Incoming-GIP_verified' => 'gip_last_action_at',
        'Incoming-TUPAD_received' => 'tupad_last_action_at',
        'Incoming-TUPAD_verified' => 'tupad_last_action_at',
        'Incoming-TEV_received' => 'tev_last_action_at',
        'Incoming-TEV_verified' => 'tev_last_action_at',
        'ORS_received' => 'budget_last_action_at',
        'ORS_verified' => 'budget_last_action_at',
        'Outgoing_received' => 'outgoing_last_action_at',
        'Outgoing_finalized' => 'outgoing_last_action_at',
        'RANCA_received' => 'accountant_last_action_at',
        'RANCA_validated' => 'accountant_last_action_at',
    ];
    
    public function updating(DvRequest $dvRequest)
    {
        if ($dvRequest->isDirty('status')) {
            $newStatus = $dvRequest->status;
            
            // Update global last_action
            $dvRequest->last_action_at = now();
            $dvRequest->last_action_type = 'status_change';
            $dvRequest->last_acted_by = auth()->id() ?? null;
            
            // Update department-specific LAST ACTION
            $field = $this->statusToDepartmentActionField[$newStatus] ?? null;
            if ($field) {
                $dvRequest->$field = now(); // ⭐ UPDATE EVERY TIME they act
                
                // Also track who acted
                $userField = str_replace('_at', '_acted_by', $field);
                if (isset($dvRequest->$userField)) {
                    $dvRequest->$userField = auth()->id();
                }
            }
        }
    }
}