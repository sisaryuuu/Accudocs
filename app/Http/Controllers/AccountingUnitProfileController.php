<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;
use App\Rules\EmailReputationCheck;

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
        $emailChanged = $request->email !== $user->email;

        // ✅ Build validation rules
        $rules = [
            'fname'         => 'required|string|max:255',
            'mi'            => 'nullable|string|max:10',
            'lname'         => 'required|string|max:255',
            'phone'         => [
                'nullable',
                'regex:/^[0-9]{11}$/',
                'unique:accountingunits,phone,' . $user->id
            ],
            'password'      => 'nullable|string|min:8|confirmed',
            'profile_image' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ];

        // ✅ Add email validation with reputation check if email is changing
        if ($emailChanged) {
            $rules['email'] = [
                'required',
                'email',
                'unique:accountingunits,email,' . $user->id,
                new EmailReputationCheck
            ];
        } else {
            $rules['email'] = [
                'required',
                'email',
                'unique:accountingunits,email,' . $user->id
            ];
        }

        $validated = $request->validate($rules, [
            'phone.regex' => 'The phone number must be exactly 11 digits.',
        ]);

        // ✅ Update basic fields (excluding password & image)
        $user->fill(collect($validated)->except(['password', 'password_confirmation', 'profile_image'])->toArray());

        // ✅ Reset email verification if email changed
        if ($emailChanged) {
            $user->email_verified_at = null;
        }

        // ✅ Password update
        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        // ✅ Profile image update
        if ($request->hasFile('profile_image')) {
            if ($user->profile_image && $user->profile_image !== 'default.png') {
                Storage::delete('public/profiles/' . $user->profile_image);
            }
            $path = $request->file('profile_image')->store('profiles', 'public');
            $user->profile_image = basename($path);
        }

        $user->save();

        // ✅ Send verification email if email changed
        if ($emailChanged) {
            try {
                $user->sendEmailVerificationNotification();
                
                // Log out the user for security
                Auth::guard('web')->logout();
                
                return redirect()->route('login')
                    ->with('success', 'Email updated! Please verify your new email address before logging in again.');
            } catch (\Exception $e) {
                \Log::error('Failed to send verification email after profile update', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage()
                ]);
                
                return redirect()->route('accountingunit.verification.notice')
                    ->with('warning', 'Email updated but verification email failed to send. Please request a new one.');
            }
        }

        return redirect()->route('web.profile.show')
            ->with('success', 'Profile updated successfully!');
    }
}