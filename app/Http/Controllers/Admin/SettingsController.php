<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function edit()
    {
        $imsd = Setting::get('imsd_head', '');
        $accountant = Setting::get('accountant_name', '');
        $regional = Setting::get('regional_director', '');
        $budgetofficer = Setting::get('budget_officer', '');

        return view('Admin.settings.edit', compact('imsd', 'accountant', 'regional','budgetofficer'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'imsd_head' => 'nullable|string|max:255',
            'accountant_name' => 'nullable|string|max:255',
            'regional_director' => 'nullable|string|max:255',
            'budget_officer' => 'nullable|string|max:255',
        ]);

        Setting::set('imsd_head', $data['imsd_head'] ?? '');
        Setting::set('accountant_name', $data['accountant_name'] ?? '');
        Setting::set('regional_director', $data['regional_director'] ?? '');
        Setting::set('budget_officer', $data['budget_officer'] ?? '');

        return redirect()->back()->with('success', 'Settings saved.');
    }
}