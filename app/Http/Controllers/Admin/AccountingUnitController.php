<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AccountingUnit;
use App\Models\Program;
use App\Rules\EmailReputationCheck;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class AccountingUnitController extends Controller
{
    public function index()
    {
        $users = AccountingUnit::with('roles', 'program')
            ->whereDoesntHave('roles', function($query) {
                $query->where('name', 'Admin');
            })
            ->get();
    
        // Get all roles for the filter dropdown
        $roles = Role::all();
        
        return view('Admin.AccountingUnits.index', compact('users', 'roles'));
    }

    public function create()
    {
        $roles = Role::all();
        $programs = Program::all();
        
        return view('Admin.AccountingUnits.create', compact('roles', 'programs'));
    }

    public function store(Request $request)
    {
        // ✅ Validate with EmailReputationCheck - UPDATED FOR MULTI-ROLE
        $validated = $request->validate([
            'fname' => 'required|string|max:100',
            'mi' => 'nullable|string|max:10',
            'lname' => 'required|string|max:100',
            'email' => [
                'required',
                'email',
                'unique:accountingunits,email',
                new EmailReputationCheck
            ],
            'phone' => [
                'nullable',
                'regex:/^[0-9]{11}$/',
                'unique:accountingunits,phone'
            ],
            'password' => 'required|min:8|confirmed',
            'roles' => 'required|array|min:1', // ✅ CHANGED: Now accepts multiple roles
            'roles.*' => 'exists:roles,name',
            'primary_role' => 'required|string|exists:roles,name', // ✅ ADDED: Primary role
            'program_id' => 'nullable|exists:programs,id',
        ], [
            'phone.regex' => 'The phone number must be exactly 11 digits and contain numbers only.',
            'phone.unique' => 'This phone number is already registered.',
            'roles.required' => 'Please select at least one role.',
            'roles.*.exists' => 'One or more selected roles are invalid.',
            'primary_role.required' => 'Please select a primary role.',
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
            'primary_role' => $validated['primary_role'], // ✅ ADDED: Set primary role
            'profile_image' => 'default.png',
            'email_verified_at' => null,
        ]);

        // ✅ Assign MULTIPLE roles
        $user->syncRoles($validated['roles']);

           \Log::info('User created with roles', [
            'user_id' => $user->id,
            'email' => $user->email,
            'assigned_roles' => $validated['roles'],
            'primary_role' => $validated['primary_role'],
            'actual_roles_after_sync' => $user->getRoleNames()->toArray()
        ]);

        // ✅ Send verification email
           try {
            \Log::info('Attempting to send verification email to: ' . $user->email);
            
            $user->sendEmailVerificationNotification();
            
            \Log::info('Verification email sent successfully to: ' . $user->email);
            
            return redirect()->route('admin.accountingunits.index')
                ->with('success', 'User created successfully with ' . count($validated['roles']) . ' roles! A verification email has been sent to ' . $user->email);
        } catch (\Exception $e) {
            \Log::error('Failed to send verification email', [
                'user_id' => $user->id,
                'email' => $user->email,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->route('admin.accountingunits.index')
                ->with('warning', 'User created but failed to send verification email. Error: ' . $e->getMessage());
        }
    }

    public function edit(AccountingUnit $accountingunit)
    {
        $roles = Role::all();
        $programs = Program::all();
        
        // ✅ Get user's current roles for pre-selection
        $userRoles = $accountingunit->roles->pluck('name')->toArray();
        $primaryRole = $accountingunit->primary_role;
        
        return view('Admin.AccountingUnits.edit', compact('accountingunit', 'roles', 'programs', 'userRoles', 'primaryRole'));
    }

    public function update(Request $request, AccountingUnit $accountingunit)
    {
        // ✅ Validate with EmailReputationCheck - UPDATED FOR MULTI-ROLE
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
            'roles' => 'required|array|min:1', // ✅ CHANGED: Now requires multiple roles
            'roles.*' => 'exists:roles,name',
            'primary_role' => 'required|string|exists:roles,name', // ✅ ADDED: Primary role
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
            'roles.required' => 'Please select at least one role.',
            'roles.*.exists' => 'One or more selected roles are invalid.',
            'primary_role.required' => 'Please select a primary role.',
        ]);

        // ✅ Check if email changed - reset verification
        $emailChanged = $request->email !== $accountingunit->email;
        
        // ✅ Update user with primary role
        $accountingunit->update([
            'fname' => $validated['fname'],
            'mi' => $validated['mi'] ?? null,
            'lname' => $validated['lname'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'program_id' => $validated['program_id'] ?? null,
            'primary_role' => $validated['primary_role'], // ✅ ADDED: Update primary role
            'email_verified_at' => $emailChanged ? null : $accountingunit->email_verified_at,
        ]);

        // ✅ Update password if provided
        if ($request->filled('password')) {
            $accountingunit->password = Hash::make($validated['password']);
            $accountingunit->save();
        }

        // ✅ Sync MULTIPLE roles
        $accountingunit->syncRoles($validated['roles']);

        // ✅ Validate that primary role is in assigned roles
        if (!in_array($validated['primary_role'], $validated['roles'])) {
            return redirect()->back()
                ->with('error', 'Primary role must be one of the assigned roles.')
                ->withInput();
        }

        // ✅ Send new verification email if email changed
        if ($emailChanged) {
            try {
                $accountingunit->sendEmailVerificationNotification();
                
                return redirect()->route('admin.accountingunits.index')
                    ->with('success', 'User updated successfully with ' . count($validated['roles']) . ' roles! A new verification email has been sent to ' . $accountingunit->email);
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
            ->with('success', 'User updated successfully with ' . count($validated['roles']) . ' roles.');
    }

    public function destroy(AccountingUnit $accountingunit)
    {
        $accountingunit->roles()->detach();
        $accountingunit->delete();

        return redirect()->route('admin.accountingunits.index')
            ->with('success', 'User deleted successfully.');
    }

    /**
     * ✅ Manually resend verification email
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

    /**
     * ✅ NEW: Quick role management actions
     */
    public function addRole(Request $request, AccountingUnit $accountingunit)
    {
        $request->validate([
            'role' => 'required|exists:roles,name'
        ]);

        $accountingunit->assignRole($request->role);

        return back()->with('success', "Role {$request->role} added successfully.");
    }

    public function removeRole(Request $request, AccountingUnit $accountingunit)
    {
        $request->validate([
            'role' => 'required|exists:roles,name'
        ]);

        // Prevent removing all roles
        if ($accountingunit->roles()->count() <= 1) {
            return back()->with('error', 'User must have at least one role.');
        }

        $accountingunit->removeRole($request->role);

        // Update primary role if it was the removed one
        if ($accountingunit->primary_role === $request->role) {
            $newPrimary = $accountingunit->roles()->first()->name;
            $accountingunit->update(['primary_role' => $newPrimary]);
        }

        return back()->with('success', "Role {$request->role} removed successfully.");
    }

    /**
     * ✅ NEW: Set primary role
     */
    public function setPrimaryRole(Request $request, AccountingUnit $accountingunit)
    {
        $request->validate([
            'primary_role' => 'required|exists:roles,name'
        ]);

        // Check if user has this role
        if (!$accountingunit->hasRole($request->primary_role)) {
            return back()->with('error', 'User does not have the specified role.');
        }

        $accountingunit->update(['primary_role' => $request->primary_role]);

        return back()->with('success', "Primary role set to {$request->primary_role}.");
    }

    /**
     * ✅ NEW: Bulk actions for multiple users
     */
    public function bulkAction(Request $request)
    {
        $request->validate([
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:accountingunits,id',
            'action' => 'required|in:assign_role,remove_role,set_primary',
            'role' => 'required_if:action,assign_role,remove_role,set_primary|exists:roles,name'
        ]);

        $users = AccountingUnit::whereIn('id', $request->user_ids)->get();
        $action = $request->action;
        $role = $request->role;

        foreach ($users as $user) {
            switch ($action) {
                case 'assign_role':
                    $user->assignRole($role);
                    break;
                case 'remove_role':
                    if ($user->roles()->count() > 1) {
                        $user->removeRole($role);
                        // Update primary role if needed
                        if ($user->primary_role === $role) {
                            $newPrimary = $user->roles()->first()->name;
                            $user->update(['primary_role' => $newPrimary]);
                        }
                    }
                    break;
                case 'set_primary':
                    if ($user->hasRole($role)) {
                        $user->update(['primary_role' => $role]);
                    }
                    break;
            }
        }

        return back()->with('success', "Bulk action completed for " . count($users) . " users.");
    }
}