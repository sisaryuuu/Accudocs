<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\FocalPerson;
use App\Models\Program;
use App\Models\ResponCenter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Rules\EmailReputationCheck;
use Illuminate\Auth\Events\Registered;
use Illuminate\Contracts\Auth\MustVerifyEmail;

class FocalPersonRegisterController extends Controller
{
    /**
     * Show the registration form for focal persons.
     */
    public function showRegistrationForm()
    {
        $programs = Program::all();
        $responCenters = ResponCenter::all();

        return view('auth.focalpersonregister', compact('programs', 'responCenters'));
    }

    /**
     * Handle registration of focal persons.
     */
    public function register(Request $request)
    {
        // ✅ Validate input with email reputation check
        $validated = $request->validate([
            'fname'          => 'required|string|max:100',
            'mi'             => 'nullable|string|max:50',
            'lname'          => 'required|string|max:100',
            'TIN_number'     => 'required|string|max:15|unique:focalpersons',
            'Address'        => 'nullable|string|max:255',
            'email'          => [
                'required', 
                'string', 
                'email', 
                'max:255', 
                'unique:focalpersons',
                new EmailReputationCheck
            ],
            'password'       => 'required|string|min:8|confirmed',
            'contactnumber'  => [
                'required',
                'regex:/^[0-9]{11}$/',
                'unique:focalpersons',
            ],
            'respocenter_id' => 'nullable|exists:responcenters,id',
        ], [
            'contactnumber.regex' => 'The contact number must be exactly 11 digits and contain numbers only.',
        ]);

        // ✅ Create focal person with hashed password
        $focalPerson = FocalPerson::create([
            'fname'          => $validated['fname'],
            'mi'             => $validated['mi'] ?? null,
            'lname'          => $validated['lname'],
            'TIN_number'     => $validated['TIN_number'],
            'Address'        => $validated['Address'] ?? null,
            'email'          => $validated['email'],
            'password'       => Hash::make($validated['password']),
            'contactnumber'  => $validated['contactnumber'],
            'respocenter_id' => $validated['respocenter_id'] ?? null,
            'profile_image'  => 'default.png',
            'email_verified_at' => null,
        ]);

        // ✅ Assign role
        $focalPerson->assignRole('Focal_Person');

        // ✅ Send verification email
        if ($focalPerson instanceof MustVerifyEmail) {
            $focalPerson->sendEmailVerificationNotification();
        }

        // ✅ Login user
        Auth::guard('focal')->login($focalPerson);

        return redirect()->route('verification.notice')
            ->with('success', 'Registration completed! Please check your email to verify your account before logging in.');
    }
}