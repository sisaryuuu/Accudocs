<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\AccountingUnit;
use App\Models\FocalPerson;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        // Check if user is already logged in
        if (Auth::guard('web')->check() || Auth::guard('focal')->check()) {
            return $this->redirectToDashboard();
        }

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
        // Check if user is already logged in
        if (Auth::guard('web')->check() || Auth::guard('focal')->check()) {
            return redirect()->route('login.redirect')->with('already_logged_in', true);
        }

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
            'Accountant_III' => 'Accounting.dvrequests.index',
            'Regional_Director' => 'regional.director.dashboard',
            'Cashier' => 'cashier.dashboard',
            'Focal_Person' => 'FocalPerson.dvrequest.index',
        ];

        // Check if user exists and get verification status
        $accountingUnitUser = AccountingUnit::where('email', $credentials['email'])->first();
        $focalPersonUser = FocalPerson::where('email', $credentials['email'])->first();

        $user = $accountingUnitUser ?: $focalPersonUser;

        if (!$user) {
            return back()->withErrors([
                'email' => 'The provided credentials do not match our records.',
            ])->withInput();
        }

        // ✅ FIX: Only check verification for AccountingUnit users, allow FocalPerson to login
        if ($user instanceof AccountingUnit && empty($user->email_verified_at)) {
            return back()
                ->withInput()
                ->with('verification_status', 'pending')
                ->withErrors([
                    'email' => 'Your account is not yet verified. Please check your email for verification instructions.'
                ]);
        }

        // ✅ REMOVED: Concurrent login check - allowing multiple sessions

        // ✅ FIXED: Try AccountingUnit guard (web) first
        if ($user instanceof AccountingUnit && Auth::guard('web')->attempt($credentials)) {
            $request->session()->regenerate();
            $user = Auth::guard('web')->user();
            
            // ✅ UPDATED: Record login but don't prevent multiple sessions
            $this->recordLoginInDatabase($user, $request->ip());
            
            // ✅ FIX: Handle multi-role session setup
            $this->setupMultiRoleSession($request, $user);
        }
        // ✅ FIXED: Then try FocalPerson guard
        else if ($user instanceof FocalPerson && Auth::guard('focal')->attempt($credentials)) {
            $request->session()->regenerate();
            $user = Auth::guard('focal')->user();
            
            // ✅ UPDATED: Record login but don't prevent multiple sessions
            $this->recordLoginInDatabase($user, $request->ip());
            
            // ✅ FIX: Handle multi-role session setup
            $this->setupMultiRoleSession($request, $user);
            
            // ✅ FIX: Check if focal person needs email verification
            if (empty($user->email_verified_at)) {
                // Redirect to verification notice instead of dashboard
                return redirect()->route('verification.notice')
                    ->with('success', 'Login successful! Please verify your email address to access all features.');
            }
        }
        else {
            return back()->withErrors([
                'email' => 'The provided credentials do not match our records.',
            ])->withInput();
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
                
                return $this->redirectToDashboard()
                    ->with('error', 'You do not have permission to access QR code status updates. Please contact administrator.');
            }
        }

        // Normal login redirect based on role
        return $this->redirectToDashboard();
    }

    /**
     * ✅ NEW: Setup multi-role session after successful login
     */
    private function setupMultiRoleSession(Request $request, $user)
    {
        // Get user's roles
        $userRoles = $user->getRoleNames()->toArray();
        
        // Set current role in session
        if (!$request->session()->has('current_role')) {
            // Use primary role if set, otherwise use first role
            $currentRole = $user->primary_role ?? $userRoles[0] ?? null;
            
            if ($currentRole) {
                $request->session()->put('current_role', $currentRole);
            }
        }

        // Store all user roles in session for quick access
        $request->session()->put('user_roles', $userRoles);
        
        // Store user's primary role
        if ($user->primary_role) {
            $request->session()->put('primary_role', $user->primary_role);
        }

        // Record login activity
        if (method_exists($user, 'recordLogin')) {
            $user->recordLogin($request->ip());
        }
    }

    public function logout(Request $request)
    {
        // Get current user before logging out
        $user = Auth::guard('web')->user() ?: Auth::guard('focal')->user();
        
        // ✅ UPDATED: Only record logout for tracking, don't prevent multiple sessions
        if ($user) {
            $this->recordLogoutInDatabase($user, $request->ip());
            
            // Record logout activity
            if (method_exists($user, 'recordLogout')) {
                $user->recordLogout();
            }
        }

        // Store redirect URL before clearing session
        $redirectUrl = route('login');
        
        // Clear all authentication and session data
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        session()->flush();

        // Return view with proper cache control headers
        return response()
            ->view('auth.logout-cleanup', ['redirectUrl' => $redirectUrl])
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }

    /**
     * ✅ UPDATED: Simplified logout tracking - no session restrictions
     */
    private function recordLogoutInDatabase($user, $ipAddress)
    {
        $userType = get_class($user);
        
        // Remove any sessions for this user from the same IP (optional cleanup)
        // This is just for cleanup, not for preventing multiple sessions
        DB::table('active_sessions')
            ->where('user_id', $user->id)
            ->where('user_type', $userType)
            ->where('ip_address', $ipAddress)
            ->delete();

        // Update user's logout timestamp for historical tracking only
        if (method_exists($user, 'update')) {
            try {
                $user->update([
                    'last_logout_at' => now(),
                    // Don't clear session_id or other fields - allow multiple sessions
                ]);
                
                \Log::info("Logout recorded for user: {$user->email} from IP: {$ipAddress}");
            } catch (\Exception $e) {
                \Log::error('Failed to update user logout timestamp: ' . $e->getMessage());
            }
        }
    }

    /**
     * Redirect for already logged-in users
     */
    public function redirectToDashboard()
    {
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
            'Accountant_III' => 'Accounting.dvrequests.index',
            'Regional_Director' => 'regional.director.dashboard',
            'Cashier' => 'cashier.dashboard',
            'Focal_Person' => 'FocalPerson.dvrequest.index',
        ];

        // Check both guards for authenticated user
        $user = Auth::guard('web')->user() ?: Auth::guard('focal')->user();
        
        if ($user) {
            // ✅ FIX: Check if focal person needs verification
            if ($user instanceof FocalPerson && empty($user->email_verified_at)) {
                return redirect()->route('verification.notice')
                    ->with('info', 'Please verify your email address to access all features.');
            }

            // ✅ FIX: Get current role from session or user's roles
            $currentRole = session('current_role');
            
            if (!$currentRole) {
                // Fallback to first role if no current role in session
                $currentRole = $user->getRoleNames()->first();
            }

            if ($currentRole && isset($roleroutes[$currentRole])) {
                return redirect()->route($roleroutes[$currentRole]);
            }
        }

        return redirect()->route('login')->withErrors([
            'email' => 'No route defined for this role.',
        ]);
    }

    /**
     * ✅ REMOVED: Force logout functionality - no longer needed
     */

    /**
     * ✅ UPDATED: Record user login in database (allows multiple sessions)
     * Now works with your existing active_sessions table schema
     */
    private function recordLoginInDatabase($user, $ipAddress)
    {
        $userType = get_class($user);
        
        // Create new session record (multiple sessions allowed per user)
        DB::table('active_sessions')->insert([
            'user_id' => $user->id,
            'user_type' => $userType,
            'ip_address' => $ipAddress,
            'login_at' => now(),
            'last_activity' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Update user's last login timestamp (but don't track single session)
        if (method_exists($user, 'update')) {
            try {
                $user->update([
                    'last_login_at' => now(),
                    'last_login_ip' => $ipAddress,
                    // Don't set session_id as multiple sessions are allowed
                    'last_logout_at' => null, // Clear logout timestamp on new login
                ]);
            } catch (\Exception $e) {
                \Log::error('Failed to update user login timestamp: ' . $e->getMessage());
            }
        }
        
        \Log::info("Login recorded for user: {$user->email} from IP: {$ipAddress} (Multiple sessions allowed)");
    }

    /**
     * Clean up expired sessions (can be called via scheduler)
     * Now just cleans up old records, doesn't enforce single sessions
     */
    public static function cleanupExpiredSessions()
    {
        // Note: In static method, use DB:: instead of $this-> or direct DB::
        $expired = \DB::table('active_sessions')
            ->where('last_activity', '<', now()->subHours(24))
            ->delete();
            
        \Log::info("Expired sessions cleaned up: {$expired} records removed.");
    }

    /**
     * ✅ REMOVED: isUserAlreadyLoggedInDatabase() method - no longer needed
     * ✅ REMOVED: clearActiveSessionsForUser() method - no longer needed  
     * ✅ REMOVED: forceLogoutOtherSessions() method - no longer needed
     */
}