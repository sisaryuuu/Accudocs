<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\FocalPerson;
use App\Models\Program;
use App\Models\ResponCenter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
     * Handle focal person registration.
     */
    public function register(Request $request)
    {
        // ✅ Validate input
        $validated = $request->validate([
            'fname'          => 'required|string|max:100',
            'mi'             => 'nullable|string|max:10',
            'lname'          => 'required|string|max:100',
            'TIN_number'     => 'required|string|max:15|unique:focalpersons',
            'Address'        => 'nullable|string|max:255',
            'email'          => 'required|string|email|max:255|unique:focalpersons',
            'password'       => 'required|string|min:8|confirmed',
            'contactnumber'  => 'required|string|max:11|unique:focalpersons',
            'respocenter_id' => 'nullable|exists:responcenters,id',
        ]);

        // ✅ Create focal person (model mutator handles password hashing)
        $focalPerson = FocalPerson::create([
            'fname'          => $validated['fname'],
            'mi'             => $validated['mi'] ?? null,
            'lname'          => $validated['lname'],
            'TIN_number'     => $validated['TIN_number'],
            'Address'        => $validated['Address'] ?? null,
            'email'          => $validated['email'],
            'password'       => $validated['password'], // hashed in model
            'contactnumber'  => $validated['contactnumber'],
            'respocenter_id' => $validated['respocenter_id'] ?? null,
            'profile_image'  => 'default.png', // ✅ enforce default
        ]);

        // ✅ Assign Focal Person role (make sure it exists in roles table)
        $focalPerson->assignRole('Focal_Person');

        // ✅ Log the focal person in using their guard
        Auth::guard('focal')->login($focalPerson);

        // ✅ Redirect to focal person dashboard
        return redirect()->route('FocalPerson.dvrequest.index')
            ->with('success', 'Focal Person registered successfully!');
    }
}
