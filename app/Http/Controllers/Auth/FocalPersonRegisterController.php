<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\FocalPerson;
use App\Models\Program;
use App\Models\ResponCenter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class FocalPersonRegisterController extends Controller
{
    public function showRegistrationForm()
    {
        $programs = Program::all();
        $responCenters = ResponCenter::all();

        return view('auth.focalpersonregister', compact('programs', 'responCenters'));
    }

    public function register(Request $request)
    {
        $request->validate([
            'fname'          => 'required|string|max:100',
            'mi'             => 'nullable|string|max:10',
            'lname'          => 'required|string|max:100',
            'TIN_number'     => 'required|string|max:15|unique:focalpersons',
            'Address'        => 'nullable|string|max:255',
            'email'          => 'required|string|email|max:255|unique:focalpersons',
            'password'       => 'required|string|min:8|confirmed',
            'contactnumber'  => 'required|string|max:11|unique:focalpersons',
            'program_id'     => 'nullable|exists:programs,id',
            'responcenter_id'=> 'nullable|exists:responcenters,id',
        ]);

        // ✅ Create focal person
        $focalPerson = FocalPerson::create([
            'fname'          => $request->fname,
            'mi'             => $request->mi,
            'lname'          => $request->lname,
            'TIN_number'     => $request->TIN_number,
            'Address'        => $request->Address,
            'email'          => $request->email,
            'contactnumber'  => $request->contactnumber,
            'password'       => Hash::make($request->password),
            'program_id'     => $request->program_id,
            'responcenter_id'=> $request->responcenter_id,
        ]);

        // ✅ Assign Focal Person role (if using Spatie Roles)
        $focalPerson->assignRole('Focal_Person');

        // ✅ Log the focal person in using their guard
        Auth::guard('focal')->login($focalPerson);

        return redirect()->route('FocalPerson.index')
            ->with('success', 'Focal Person registered successfully!');
    }
}
