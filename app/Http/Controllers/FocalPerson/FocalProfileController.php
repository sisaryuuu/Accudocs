<?php

namespace App\Http\Controllers\FocalPerson;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class FocalProfileController extends Controller
{
    /**
     * Show profile.
     */
    public function show()
    {
        $user = Auth::guard('focal')->user();
        return view('FocalPerson.profile.show', compact('user'));
    }

    /**
     * Edit profile form.
     */
    public function edit()
    {
        $user = Auth::guard('focal')->user();
        return view('FocalPerson.profile.edit', compact('user'));
    }

    /**
     * Update profile.
     */
    public function update(Request $request)
{
    $user = Auth::guard('focal')->user();

    $validated = $request->validate([
        'fname'         => 'required|string|max:255',
        'mi'            => 'nullable|string|max:5',
        'lname'         => 'required|string|max:255',
        'email'         => 'required|email|unique:focalpersons,email,' . $user->id,
        'contactnumber' => 'nullable|string|max:11',
        'password'      => 'nullable|string|min:8|confirmed',
        'profile_image' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
    ]);

    // Update basic fields
    $user->fill(collect($validated)->except(['password', 'password_confirmation'])->toArray());

    // If password provided, assign directly (mutator will hash it)
    if (!empty($validated['password'])) {
        $user->password = $validated['password'];
    }
    // Handle profile image upload
    if ($request->hasFile('profile_image')) {
        // Delete old image if not default
        if ($user->profile_image && $user->profile_image !== 'default.png') {
            Storage::delete('public/profiles/' . $user->profile_image);
        }

        // Store new image automatically in storage/app/public/profiles
        $path = $request->file('profile_image')->store('profiles', 'public');

        // Only save filename, not full path
        $user->profile_image = basename($path);
    }

    $user->save();

    return redirect()->route('FocalPerson.profile.show')
        ->with('success', 'Profile updated successfully!');
}
}