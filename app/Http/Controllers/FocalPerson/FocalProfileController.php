<?php

namespace App\Http\Controllers\FocalPerson;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use App\Rules\EmailReputationCheck;

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
        $emailChanged = $request->email !== $user->email;

        // ✅ Build validation rules
        $rules = [
            'fname'         => 'required|string|max:255',
            'mi'            => 'nullable|string|max:5',
            'lname'         => 'required|string|max:255',
            'contactnumber' => [
                'nullable',
                'regex:/^[0-9]{11}$/',
                'unique:focalpersons,contactnumber,' . $user->id
            ],
            'password'      => 'nullable|string|min:8|confirmed',
            'profile_image' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ];

        // ✅ Add email validation with reputation check if email is changing
        if ($emailChanged) {
            $rules['email'] = [
                'required',
                'email',
                'unique:focalpersons,email,' . $user->id,
                new EmailReputationCheck
            ];
        } else {
            $rules['email'] = [
                'required',
                'email',
                'unique:focalpersons,email,' . $user->id
            ];
        }

        $validated = $request->validate($rules, [
            'contactnumber.regex' => 'The contact number must be exactly 11 digits.',
        ]);

        // ✅ Update basic fields
        $user->fill(collect($validated)->except(['password', 'password_confirmation', 'profile_image'])->toArray());

        // ✅ Reset email verification if email changed
        if ($emailChanged) {
            $user->email_verified_at = null;
        }

        // ✅ If password provided, assign directly (mutator will hash it)
        if (!empty($validated['password'])) {
            $user->password = $validated['password'];
        }

        // ✅ Handle profile image upload
        if ($request->hasFile('profile_image')) {
            // Delete old image if not default
            if ($user->profile_image && $user->profile_image !== 'default.png') {
                Storage::delete('public/profiles/' . $user->profile_image);
            }

            // Store new image
            $path = $request->file('profile_image')->store('profiles', 'public');
            $user->profile_image = basename($path);
        }

        $user->save();

        // ✅ Send verification email if email changed
        if ($emailChanged) {
            try {
                $user->sendEmailVerificationNotification();
                
                // Log out the user for security
                Auth::guard('focal')->logout();
                
                return redirect()->route('login')
                    ->with('success', 'Email updated! Please verify your new email address before logging in again.');
            } catch (\Exception $e) {
                \Log::error('Failed to send verification email after profile update', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage()
                ]);
                
                return redirect()->route('verification.notice')
                    ->with('warning', 'Email updated but verification email failed to send. Please request a new one.');
            }
        }

        return redirect()->route('FocalPerson.profile.show')
            ->with('success', 'Profile updated successfully!');
    }
}