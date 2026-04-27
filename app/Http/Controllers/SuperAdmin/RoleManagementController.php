<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class RoleManagementController extends Controller
{
    public function index()
    {
        return Inertia::render('SuperAdmin/Roles', [
            'roles' => Role::with('permissions')->get(),
            'permissions' => Permission::all(),
            'users' => User::with('roles')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:roles,name',
            'permissions' => 'array',
        ]);

        DB::transaction(function () use ($request) {
            $role = Role::create(['name' => $request->name]);
            if ($request->has('permissions')) {
                $role->syncPermissions($request->permissions);
            }
        });

        return redirect()->back()->with('success', 'Role created successfully.');
    }

    public function update(Request $request, Role $role)
    {
        $request->validate([
            'name' => 'required|string|unique:roles,name,' . $role->id,
            'permissions' => 'array',
        ]);

        if ($role->name === 'Super Admin' && $request->name !== 'Super Admin') {
            return redirect()->back()->with('error', 'Cannot rename Super Admin role.');
        }

        DB::transaction(function () use ($request, $role) {
            $role->update(['name' => $request->name]);
            $role->syncPermissions($request->permissions);
        });

        return redirect()->back()->with('success', 'Role updated successfully.');
    }

    public function destroy(Role $role)
    {
        if ($role->name === 'Super Admin') {
            return redirect()->back()->with('error', 'Cannot delete Super Admin role.');
        }

        $role->delete();

        return redirect()->back()->with('success', 'Role deleted successfully.');
    }
}
