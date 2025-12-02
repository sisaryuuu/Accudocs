<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleController extends Controller
{
    // -------------------
    // Role Management
    // -------------------
    public function index()
    {
        $roles = Role::with('permissions')->get();
        return view('Admin.Roles.index', compact('roles'));
    }

    public function create()
    {
        $permissions = Permission::all();
        return view('Admin.Roles.create', compact('permissions'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:roles,name',
            'permissions' => 'nullable|array'
        ]);

        $role = Role::create(['name' => $request->name]);
        if ($request->permissions) {
            $role->syncPermissions($request->permissions);
        }

        return redirect()->route('admin.roles.index')->with('success', 'Role created successfully.');
    }

    public function edit(Role $role)
    {
        $permissions = Permission::all();
        return view('Admin.Roles.edit', compact('role', 'permissions')); // Fixed: consistent case
    }

    public function update(Request $request, Role $role)
    {
        $request->validate([
            'name' => 'required|unique:roles,name,'.$role->id,
            'permissions' => 'nullable|array'
        ]);

        $role->update(['name' => $request->name]);
        $role->syncPermissions($request->permissions);

        return redirect()->route('admin.roles.index')->with('success', 'Role updated successfully.'); // Fixed: lowercase
    }

    public function destroy(Role $role)
    {
        $role->delete();
        return redirect()->route('admin.roles.index')->with('success', 'Role deleted successfully.'); // Fixed: lowercase
    }

    // -------------------
    // Permission Management
    // -------------------

    public function permissionsIndex()
    {
        $permissions = Permission::all();
        return view('Admin.Permissions.index', compact('permissions'));
    }

    public function permissionsCreate()
    {
        $roles = Role::all();
        return view('Admin.Permissions.create', compact('roles'));
    }

    public function permissionsStore(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:permissions,name',
        ]);

        $permission = Permission::create(['name' => $request->name]);
        
        if ($request->roles) {
            foreach ($request->roles as $roleId) {
                $role = Role::find($roleId);
                if ($role) {
                    $role->givePermissionTo($permission);
                }
            }
        }

        return redirect()->route('admin.permissions.index')->with('success', 'Permission created successfully.');
    }

    public function permissionsEdit(Permission $permission)
    {
        $roles = Role::all(); // Add this line to get roles for the view
        return view('Admin.Permissions.edit', compact('permission', 'roles'));
    }

    public function permissionsUpdate(Request $request, Permission $permission)
    {
        $request->validate([
            'name' => 'required|unique:permissions,name,'.$permission->id,
        ]);

        $permission->update(['name' => $request->name]);

        return redirect()->route('admin.permissions.index')->with('success', 'Permission updated successfully.');
    }

    public function permissionsDestroy(Permission $permission)
    {
        $permission->delete();
        return redirect()->route('admin.permissions.index')->with('success', 'Permission deleted successfully.');
    }
}