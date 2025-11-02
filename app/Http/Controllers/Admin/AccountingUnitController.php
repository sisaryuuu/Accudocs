<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AccountingUnit;
use App\Models\Program;
use App\Rules\EmailReputationCheck;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class AccountingUnitController extends Controller
{
    public function index()
    {
        $users = AccountingUnit::with('roles', 'program')
            ->whereDoesntHave('roles', function($query) {
                $query->where('name', 'Admin');
            })
            ->get();
        
        return view('admin.accountingUnits.index', compact('users'));
    }

    public function create()
    {
        $roles = Role::all();
        $programs = Program::all();
        
        return view('admin.accountingUnits.create', compact('roles', 'programs'));
    }

    public function store(Request $request)
    {
        // ✅ Validate with EmailReputationCheck
        $validated = $request->validate([
            'fname' => 'required|string|max:100',
            'mi' => 'nullable|string|max:10',
            'lname' => 'required|string|max:100',
            'email' => [
                'required',
                'email',
                'unique:accountingunits,email',
                new EmailReputationCheck // ✅ Add email reputation check
            ],
            'phone' => [
                'nullable',
                'regex:/^[0-9]{11}$/',
                'unique:accountingunits,phone'
            ],
            'password' => 'required|min:8|confirmed',
            'role' => 'required|exists:roles,name',
            'program_id' => 'nullable|exists:programs,id',
        ], [
            'phone.regex' => 'The phone number must be exactly 11 digits and contain numbers only.',
            'phone.unique' => 'This phone number is already registered.',
        ]);

        // ✅ Create accounting unit
        $user = AccountingUnit::create([
            'fname' => $validated['fname'],
            'mi' => $validated['mi'] ?? null,
            'lname' => $validated['lname'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'password' => Hash::make($validated['password']),
            'program_id' => $validated['program_id'] ?? null,
            'profile_image' => 'default.png',
            'email_verified_at' => null, // ✅ Unverified by default
        ]);

        // ✅ Assign role
        $user->assignRole($validated['role']);

        // ✅ Send verification email
        try {
            $user->sendEmailVerificationNotification();
            
            return redirect()->route('admin.accountingunits.index')
                ->with('success', 'User created successfully! A verification email has been sent to ' . $user->email);
        } catch (\Exception $e) {
            \Log::error('Failed to send verification email', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            
            return redirect()->route('admin.accountingunits.index')
                ->with('warning', 'User created but failed to send verification email. Please resend manually.');
        }
    }

    public function edit(AccountingUnit $accountingunit)
    {
        $roles = Role::all();
        $programs = Program::all();
        
        return view('admin.accountingunits.edit', compact('accountingunit', 'roles', 'programs'));
    }

    public function update(Request $request, AccountingUnit $accountingunit)
    {
        // ✅ Validate with EmailReputationCheck (only if email changed)
        $rules = [
            'fname' => 'required|string|max:100',
            'mi' => 'nullable|string|max:10',
            'lname' => 'required|string|max:100',
            'program_id' => 'nullable|exists:programs,id',
            'phone' => [
                'nullable',
                'regex:/^[0-9]{11}$/',
                'unique:accountingunits,phone,' . $accountingunit->id
            ],
            'password' => 'nullable|min:8|confirmed',
            'roles' => 'nullable|array',
        ];

        // ✅ Add email validation with reputation check if email is being changed
        if ($request->email !== $accountingunit->email) {
            $rules['email'] = [
                'required',
                'email',
                'unique:accountingunits,email,' . $accountingunit->id,
                new EmailReputationCheck
            ];
        } else {
            $rules['email'] = [
                'required',
                'email',
                'unique:accountingunits,email,' . $accountingunit->id
            ];
        }

        $validated = $request->validate($rules, [
            'phone.regex' => 'The phone number must be exactly 11 digits and contain numbers only.',
        ]);

        // ✅ Check if email changed - reset verification
        $emailChanged = $request->email !== $accountingunit->email;
        
        $accountingunit->update([
            'fname' => $validated['fname'],
            'mi' => $validated['mi'] ?? null,
            'lname' => $validated['lname'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'program_id' => $validated['program_id'] ?? null,
            'email_verified_at' => $emailChanged ? null : $accountingunit->email_verified_at, // ✅ Reset if email changed
        ]);

        // ✅ Update password if provided
        if ($request->filled('password')) {
            $accountingunit->password = Hash::make($validated['password']);
            $accountingunit->save();
        }

        // ✅ Sync roles
        if ($request->has('roles')) {
            $accountingunit->syncRoles($validated['roles']);
        }

        // ✅ Send new verification email if email changed
        if ($emailChanged) {
            try {
                $accountingunit->sendEmailVerificationNotification();
                
                return redirect()->route('admin.accountingunits.index')
                    ->with('success', 'User updated successfully! A new verification email has been sent to ' . $accountingunit->email);
            } catch (\Exception $e) {
                \Log::error('Failed to send verification email', [
                    'user_id' => $accountingunit->id,
                    'error' => $e->getMessage()
                ]);
                
                return redirect()->route('admin.accountingunits.index')
                    ->with('warning', 'User updated but failed to send verification email.');
            }
        }

        return redirect()->route('admin.accountingunits.index')
            ->with('success', 'User updated successfully.');
    }

    public function destroy(AccountingUnit $accountingunit)
    {
        $accountingunit->roles()->detach();
        $accountingunit->delete();

        return redirect()->route('admin.accountingunits.index')
            ->with('success', 'User deleted successfully.');
    }

    /**
     * ✅ Manually resend verification email (optional feature)
     */
    public function resendVerification(AccountingUnit $accountingunit)
    {
        if ($accountingunit->hasVerifiedEmail()) {
            return back()->with('info', 'This user has already verified their email.');
        }

        try {
            $accountingunit->sendEmailVerificationNotification();
            
            return back()->with('success', 'Verification email sent successfully to ' . $accountingunit->email);
        } catch (\Exception $e) {
            \Log::error('Failed to resend verification email', [
                'user_id' => $accountingunit->id,
                'error' => $e->getMessage()
            ]);
            
            return back()->with('error', 'Failed to send verification email. Please try again later.');
        }
    }
}