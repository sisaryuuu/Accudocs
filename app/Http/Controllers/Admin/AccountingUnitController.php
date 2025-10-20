<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AccountingUnit;
use App\Models\Program;
use Illuminate\Http\Request;
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
        
        return view('admin.accountingUnits.create', compact('roles'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'fname' => 'required|string|max:100',
            'mi' => 'nullable|string|max:10',
            'lname' => 'required|string|max:100',
            'email' => 'required|email|unique:accountingunits,email',
            'phone' => 'nullable|string|max:11',
            'password' => 'required|min:6|confirmed',
            'roles' => 'nullable|array',
        ]);

        $user = AccountingUnit::create([
            'fname' => $request->fname,
            'mi' => $request->mi,
            'lname' => $request->lname,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => $request->password,

        ]);

        $user->assignRole($request->role);

        return redirect()->route('admin.accountingunits.index')->with('success', 'User created successfully.');
    }

    public function edit(AccountingUnit $accountingunit)
    {
        $roles = Role::all();
        return view('admin.accountingunits.edit', compact('accountingunit', 'roles'));
    }

    public function update(Request $request, AccountingUnit $accountingunit)
    {
        $request->validate([
            'fname' => 'required|string|max:100',
            'mi' => 'nullable|string|max:10',
            'lname' => 'required|string|max:100',
            'program_id' => 'nullable|exists:programs,id',
            'email' => 'required|email|unique:accountingunits,email,' . $accountingunit->id,
            'phone' => 'nullable|max:11',
            'password' => 'nullable|min:6|confirmed',
            'roles' => 'nullable|array',
        ]);

        $accountingunit->update([
            'fname' => $request->fname,
            'mi' => $request->mi,
            'lname' => $request->lname,
            'email' => $request->email,
            'program_id' => $request->program_id,
        ]);

        if ($request->password) {
            $accountingunit->password = $request->password;
            $accountingunit->save();
        }

        if ($request->has('roles')) {
            $accountingunit->syncRoles($request->roles);
        }

        return redirect()->route('accountingunits.index')->with('success', 'User updated successfully.');
    }

    public function destroy(AccountingUnit $AccountingUnit)
    {
     
    $AccountingUnit->roles()->detach(); // remove all role associations
    $AccountingUnit->delete();

    return redirect()->route('admin.accountingunits.index')
                 ->with('success', 'User deleted successfully.');
    }
}