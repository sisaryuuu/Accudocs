<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class RoleSwitchController extends Controller
{
    public function switchRole(Request $request)
    {
        $request->validate([
            'role' => 'required|string'
        ]);

        $user = Auth::user();
        $requestedRole = $request->role;

        Log::info('Role switch attempt', [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'requested_role' => $requestedRole,
            'available_roles' => $user->getRoleNames()->toArray()
        ]);

        // Check if user has the role
        if (!$user->hasRole($requestedRole)) {
            return back()->with('error', "You don't have the '{$requestedRole}' role.");
        }

        // Store the new role in session
        session(['current_role' => $requestedRole]);

        Log::info('Role switched successfully', [
            'user_id' => $user->id,
            'new_role' => $requestedRole
        ]);

        // Redirect to the appropriate dashboard
        return redirect()->route($this->getDashboardRouteForRole($requestedRole))
                        ->with('success', "Role switched to {$requestedRole} successfully.");
    }

    protected function getDashboardRouteForRole($role)
    {
        $roleRoutes = [
            'Admin' => 'Admin.dashboard',
            'IMSD' => 'RegionalOffices.IMSD.index',
            'TSSD' => 'Regional Offices.TSSD.index',
            'MALSU' => 'Regional Offices.MALSU.index',
            'Incoming-TUPAD' => 'Incoming.TUPAD.index',
            'Incoming-GIP' => 'Incoming.GIP.index',
            'Incoming-TEV' => 'Incoming.TEV.index',
            'Budget_Officer' => 'BudgetOfficer.dvrequests.index',
            'Outgoing' => 'Outgoing.dvrequests.index',
            'Accountant_III' => 'Accounting.dvrequests.index',
            'Regional_Director' => 'regional.director.dashboard',
            'Cashier' => 'cashier.dashboard',
            'Focal_Person' => 'FocalPerson.dvrequest.index',
        ];

        return $roleRoutes[$role] ?? 'dashboard';
    }

    public function getCurrentRole()
    {
        $user = Auth::user();
        
        return response()->json([
            'current_role' => session('current_role', $user->primary_role ?? $user->getRoleNames()->first()),
            'user_roles' => $user->getRoleNames()->toArray(),
            'has_multiple_roles' => $user->roles->count() > 1,
        ]);
    }
}