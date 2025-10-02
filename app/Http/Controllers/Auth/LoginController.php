<?php

namespace App\Http\Controllers\Auth;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
class LoginController extends Controller
{
    public function showLoginForm()
    {
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

    // Redirect based on role
    $role = $user->getRoleNames()->first();
    if ($role && isset($roleroutes[$role])) {
        return redirect()->route($roleroutes[$role]);
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

        return redirect()->route('login');
    }

}
