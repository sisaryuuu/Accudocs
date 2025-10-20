<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;

class AccountingUnitProfileController extends Controller
{
    /**
     * Show profile.
     */
    public function show()
    {
        $user = Auth::guard('web')->user();
        return view('web.profile.show', compact('user'));
    }

    /**
     * Edit profile form.
     */
    public function edit()
    {
        $user = Auth::guard('web')->user();
        return view('web.profile.edit', compact('user'));
    }

    /**
     * Update profile.
     */
    public function update(Request $request)
    {
        $user = Auth::guard('web')->user();

        $validated = $request->validate([
           'fname'         => 'required|string|max:255',
            'mi'            => 'nullable|string|max:10',
            'lname'         => 'required|string|max:255',
            'email'         => 'required|email|unique:accountingunits,email,' . $user->id, 
            'phone'         => 'nullable|string|max:11',
            'password'      => 'nullable|string|min:8|confirmed',
            'profile_image' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        // Update basic fields (excluding password & image)
        $user->fill(collect($validated)->except(['password', 'password_confirmation', 'profile_image'])->toArray());

        // Password update
        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        // Profile image update
        if ($request->hasFile('profile_image')) {
            if ($user->profile_image && $user->profile_image !== 'default.png') {
                Storage::delete('public/profiles/' . $user->profile_image);
            }
            $path = $request->file('profile_image')->store('profiles', 'public');
            $user->profile_image = basename($path);
        }

        $user->save();

        return redirect()->route('web.profile.show')->with('success', 'Profile updated successfully!');
    }
}
