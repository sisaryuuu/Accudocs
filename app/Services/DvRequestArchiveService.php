<?php
// app/Services/DvRequestArchiveService.php

namespace App\Services;

use App\Models\DvRequest;
use App\Models\DvRequestLog;
use App\Models\DvRequestsArchive;
use Illuminate\Support\Facades\DB;

class DvRequestArchiveService
{
    public function archiveCompletedRequest(DvRequest $dvRequest, $archivedBy = null)
    {
        return DB::transaction(function () use ($dvRequest, $archivedBy) {
            
            // 1. Copy the request to archive table
            $archiveData = $dvRequest->toArray();
            
            // Add archive-specific fields
            $archiveData['original_status'] = $dvRequest->status;
            $archiveData['archive_reason'] = 'Request completed successfully';
            $archiveData['completed_at'] = now();
            $archiveData['archived_at'] = now();
            $archiveData['archived_by'] = $archivedBy;

            // Remove the id to avoid conflict
            unset($archiveData['id']);

            $archivedRequest = DvRequestsArchive::create($archiveData);

            // 2. Delete the original request and logs
            DvRequestLog::where('dv_request_id', $dvRequest->id)->delete();
            $dvRequest->delete();

            return $archivedRequest;
        });
    }

    public function archiveRejectedRequest(DvRequest $dvRequest, $rejectionRemarks = null, $archivedBy = null)
    {
        return DB::transaction(function () use ($dvRequest, $rejectionRemarks, $archivedBy) {
            
            // Get the specific rejection reason
            $rejectionReason = $this->getRejectionReason($dvRequest->status, $rejectionRemarks);
            
            // 1. Copy the request to archive table
            $archiveData = $dvRequest->toArray();
            
            // Add archive-specific fields
            $archiveData['original_status'] = $dvRequest->status;
            $archiveData['archive_reason'] = $rejectionReason;
            $archiveData['completed_at'] = now();
            $archiveData['archived_at'] = now();
            $archiveData['archived_by'] = $archivedBy;

            // Remove the id to avoid conflict
            unset($archiveData['id']);

            $archivedRequest = DvRequestsArchive::create($archiveData);

            // 2. Delete the original request and logs
            DvRequestLog::where('dv_request_id', $dvRequest->id)->delete();
            $dvRequest->delete();

            return $archivedRequest;
        });
    }

    private function getRejectionReason($status, $customRemarks = null)
    {
        if ($customRemarks) {
            return $customRemarks;
        }

        $rejectionReasons = [
            'IMSD_rejected' => 'Rejected by IMSD - Document verification failed',
            'Incoming-TUPAD_rejected' => 'Rejected by Incoming TUPAD - Requirements incomplete',
            'Incoming-GIP_rejected' => 'Rejected by Incoming GIP - Requirements incomplete', 
            'Incoming-TEV_rejected' => 'Rejected by Incoming TEV - Requirements incomplete',
            'ORS_rejected' => 'Rejected by Budget Officer - Obligation request denied',
            'ORS_onhold' => 'Put on hold by Budget Officer - Additional information required',
            'ORS_for_revision' => 'Returned for revision by Budget Officer',
            'Outgoing_rejected' => 'Rejected by Outgoing - Finalization failed',
            'RANCA_rejected' => 'Rejected by Accounting - Validation failed',
        ];

        return $rejectionReasons[$status] ?? 'Request rejected - ' . $status;
    }

    public function isRejectedStatus($status)
    {
        $rejectedStatuses = [
            'IMSD_rejected',
            'Incoming-TUPAD_rejected',
            'Incoming-GIP_rejected',
            'Incoming-TEV_rejected', 
            'ORS_rejected',
            'ORS_onhold',
            'ORS_for_revision',
            'Outgoing_rejected',
            'RANCA_rejected'
        ];

        return in_array($status, $rejectedStatuses) || stripos($status, 'rejected') !== false;
    }
}