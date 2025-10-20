<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        // Check if there's a QR redirect in URL or session
        $qrToken = request('qr_redirect') ?: session('qr_redirect_token');
        
        if ($qrToken && !session('qr_redirect_token')) {
            // Store in session for later use
            session(['qr_redirect_token' => $qrToken]);
        }

        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        $roleroutes = [
            'Admin' => 'Admin.dashboard',
            'IMSD' => 'RegionalOffices.IMSD.index',
            'TSSD' => 'Regional Offices.TSSD.index',
            'MALSU' => 'Regional Offices.MALSU.index',
            'Incoming-TUPAD' => 'Incoming.TUPAD.index',
            'Incoming-GIP' => 'Incoming.GIP.index',
            'Incoming-TEV' => 'Incoming.TEV.index',
            'Budget_Officer' => 'BudgetOfficer.dvrequests.index',
            'Outgoing' => 'Outgoing.dvrequests.index',
            'Accounting_III' => 'Accounting.dvrequests.index',
            'Regional_Director' => 'regional.director.scanner',
            'Cashier' => 'cashier.scanner',
            'Focal_Person' => 'FocalPerson.dvrequest.index',
        ];

        // Try AccountingUnit guard first
        if (Auth::guard('web')->attempt($credentials, $request->filled('remember'))) {
            $request->session()->regenerate();
            $user = Auth::guard('web')->user();
        }
        // Try FocalPerson guard
        elseif (Auth::guard('focal')->attempt($credentials, $request->filled('remember'))) {
            $request->session()->regenerate();
            $user = Auth::guard('focal')->user();
        }
        else {
            return back()->withErrors([
                'email' => 'The provided credentials do not match our records.',
            ]);
        }

        // Check if there's a QR redirect waiting
        $qrToken = session('qr_redirect_token');

        if ($qrToken) {
            // Check if user has the right role for QR access
            $requiredRoles = ['Cashier', 'Regional_Director'];
            
            if ($user->hasAnyRole($requiredRoles)) {
                // Clear the session data
                session()->forget('qr_redirect_token');
                
                // Redirect back to the QR status update interface
                return redirect()->route('dv.qr.interface', $qrToken)
                    ->with('success', 'Login successful! You can now update the DV status.');
            } else {
                // User doesn't have the right role
                session()->forget('qr_redirect_token');
                
                $role = $user->getRoleNames()->first();
                $dashboardRoute = $roleroutes[$role] ?? 'login';
                
                return redirect()->route($dashboardRoute)
                    ->with('error', 'You do not have permission to access QR code status updates. Please contact administrator.');
            }
        }

        // Normal login redirect based on role
        $role = $user->getRoleNames()->first();
        if ($role && isset($roleroutes[$role])) {
            return redirect()->route($roleroutes[$role])
                ->with('success', 'Login successful!');
        }

        return redirect()->route('login')->withErrors([
            'email' => 'No route defined for this role.',
        ]);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')
            ->with('info', 'You have been logged out successfully.');
    }
}