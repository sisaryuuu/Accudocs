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
            'TIN_number'     => [
                'required',
                'string',
                'max:20',
                'unique:focalpersons',
                'regex:/^(\d{9}|\d{13}|\d{3}-\d{3}-\d{3}|\d{3}-\d{3}-\d{3}-\d{4})$/'
            ],
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
            'TIN_number.regex' => 'The TIN number must be 9 digits (000-000-000) or 13 digits (000-000-000-0000).',
            'TIN_number.max' => 'The TIN number must not exceed 16 characters.',
            'contactnumber.regex' => 'The contact number must be exactly 11 digits and contain numbers only.',
        ]);

        // ✅ Format TIN number consistently
        $tinNumber = $validated['TIN_number'];
        $cleanTin = preg_replace('/[^0-9]/', '', $tinNumber);
        
        if (!in_array(strlen($cleanTin), [9, 13])) {
            return redirect()->back()
                ->withInput()
                ->withErrors([
                    'TIN_number' => 'The TIN number must be exactly 9 or 13 digits long.'
                ]);
        }
        
        if (strlen($cleanTin) === 9) {
            $formattedTin = substr($cleanTin, 0, 3) . '-' . 
                           substr($cleanTin, 3, 3) . '-' . 
                           substr($cleanTin, 6, 3);
        } else {
            $formattedTin = substr($cleanTin, 0, 3) . '-' . 
                           substr($cleanTin, 3, 3) . '-' . 
                           substr($cleanTin, 6, 3) . '-' . 
                           substr($cleanTin, 9, 4);
        }

        // ✅ Create focal person
        $focalPerson = FocalPerson::create([
            'fname'          => $validated['fname'],
            'mi'             => $validated['mi'] ?? null,
            'lname'          => $validated['lname'],
            'TIN_number'     => $formattedTin,
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

        // ✅ Redirect with success message
        return redirect()->route('login')
            ->with('registration_success', 'Registration completed! Please login your account for email verification.');
    }
}