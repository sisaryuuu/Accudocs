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
        // Exclude Focal_Person role from the list
        $roles = Role::with('permissions')
                    ->where('name', '!=', 'Focal_Person')
                    ->get();
        return view('Admin.Roles.index', compact('roles'));
    }

    public function create()
    {
        // Get all permissions (you might want to filter these too if needed)
        $permissions = Permission::all();
        return view('Admin.Roles.create', compact('permissions'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:roles,name',
            'permissions' => 'nullable|array'
        ]);

        // Prevent creating a role named Focal_Person
        if (strtolower($request->name) === 'focal_person' || strtolower($request->name) === 'focal person') {
            return redirect()->back()
                ->withInput()
                ->withErrors(['name' => 'Cannot create a role with this name.']);
        }

        $role = Role::create(['name' => $request->name]);
        if ($request->permissions) {
            $role->syncPermissions($request->permissions);
        }

        return redirect()->route('admin.roles.index')->with('success', 'Role created successfully.');
    }

    public function edit(Role $role)
    {
        // Prevent editing Focal_Person role
        if ($role->name === 'Focal_Person') {
            abort(403, 'This role cannot be edited.');
        }
        
        $permissions = Permission::all();
        return view('Admin.Roles.edit', compact('role', 'permissions'));
    }

    public function update(Request $request, Role $role)
    {
        // Prevent updating Focal_Person role
        if ($role->name === 'Focal_Person') {
            abort(403, 'This role cannot be modified.');
        }
        
        $request->validate([
            'name' => 'required|unique:roles,name,'.$role->id,
            'permissions' => 'nullable|array'
        ]);

        // Prevent renaming to Focal_Person
        if (strtolower($request->name) === 'focal_person' || strtolower($request->name) === 'focal person') {
            return redirect()->back()
                ->withInput()
                ->withErrors(['name' => 'Cannot rename to this role name.']);
        }

        $role->update(['name' => $request->name]);
        $role->syncPermissions($request->permissions);

        return redirect()->route('admin.roles.index')->with('success', 'Role updated successfully.');
    }

    public function destroy(Role $role)
    {
        // Prevent deleting Focal_Person role
        if ($role->name === 'Focal_Person') {
            abort(403, 'This role cannot be deleted.');
        }
        
        $role->delete();
        return redirect()->route('admin.roles.index')->with('success', 'Role deleted successfully.');
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
        // Exclude Focal_Person role from dropdown
        $roles = Role::where('name', '!=', 'Focal_Person')->get();
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
                if ($role && $role->name !== 'Focal_Person') {
                    $role->givePermissionTo($permission);
                }
            }
        }

        return redirect()->route('admin.permissions.index')->with('success', 'Permission created successfully.');
    }

    public function permissionsEdit(Permission $permission)
    {
        // Exclude Focal_Person role from dropdown
        $roles = Role::where('name', '!=', 'Focal_Person')->get();
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