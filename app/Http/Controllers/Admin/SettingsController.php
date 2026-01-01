<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function edit()
    {
        $settings = [
            'imsd_head' => Setting::get('imsd_head', ''),
            'accountant_name' => Setting::get('accountant_name', ''),
            'regional_director' => Setting::get('regional_director', ''),
            'budget_officer' => Setting::get('budget_officer', ''),
            'budget_tupad' => Setting::get('budget_tupad', 0.00),
            'budget_gip' => Setting::get('budget_gip', 0.00),
            'budget_tev' => Setting::get('budget_tev', 0.00),
        ];

        return view('Admin.settings.edit', compact('settings'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'imsd_head' => 'nullable|string|max:255',
            'accountant_name' => 'nullable|string|max:255',
            'regional_director' => 'nullable|string|max:255',
            'budget_officer' => 'nullable|string|max:255',
            'budget_tupad' => 'required|numeric|min:0',
            'budget_gip' => 'required|numeric|min:0',
            'budget_tev' => 'required|numeric|min:0',
        ]);

        foreach ($data as $key => $value) {
            Setting::set($key, $value);
        }

        return redirect()->back()->with('success', 'Settings saved successfully.');
    }
}