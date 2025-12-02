<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class CheckSessionTimeout
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only check for authenticated users
        if (Auth::check()) {
            $user = Auth::user();
            $userType = get_class($user);
            
            // Check if session exists in active_sessions table
            $activeSession = DB::table('active_sessions')
                ->where('user_id', $user->id)
                ->where('user_type', $userType)
                ->where('last_activity', '>=', now()->subHours(24))
                ->first();

            if (!$activeSession) {
                // Session expired or logged out from another device
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                
                return redirect()->route('login')
                    ->with('error', 'Your session has expired due to inactivity or login from another device.');
            }

            // Update last activity for active session
            DB::table('active_sessions')
                ->where('user_id', $user->id)
                ->where('user_type', $userType)
                ->update(['last_activity' => now()]);
        }

        return $next($request);
    }
}