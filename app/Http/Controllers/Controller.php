<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Auth;
use App\Models\DVRequest; // 👈 don’t forget this import!

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    // 👇 Sidebar Counts Helper
   protected function getSidebarCounts()
{
   $currentUserId = Auth::id();
    $lockDuration  = now()->subMinutes(10);

    // Helper to filter only unlocked or self-locked requests
    $filterUnlocked = function ($query) use ($currentUserId, $lockDuration) {
        $query->whereNull('locked_by')
              ->orWhere('locked_by', $currentUserId)
              ->orWhere('locked_at', '<=', $lockDuration);
    };

    return [
        // IMSD → count submitted + in_process
        'IMSD' => DVRequest::whereIn('status', ['submitted', 'IMSD_in_process'])
            ->where($filterUnlocked)
            ->count(),

        // Incoming-GIP
        'Incoming-GIP' => DVRequest::whereIn('status', ['IMSD_verified', 'Incoming-GIP_in_process'])
            ->whereHas('program', fn($q) => $q->where('prcode', 'GIP')) 
            ->where($filterUnlocked)
            ->count(),

        // Incoming-TUPAD
        'Incoming-TUPAD' => DVRequest::whereIn('status', ['IMSD_verified', 'Incoming-TUPAD_in_process'])
            ->whereHas('program', fn($q) => $q->where('prcode', 'TUPAD')) 
            ->where($filterUnlocked)
            ->count(),

        // Budget Officer
        'BudgetOfficer' => DVRequest::whereIn('status', [
                'Incoming-TUPAD_verified', 'Incoming-GIP_verified','ORS_received','ORS_onhold'
            ])
            ->where($filterUnlocked)
            ->count(),

        // Outgoing
        'Outgoing' => DVRequest::whereIn('status', [
                'ORS_obligated','Outgoing_received','Outgoing_finalized'
            ])
            ->where($filterUnlocked)
            ->count(),
    ];
}
}
