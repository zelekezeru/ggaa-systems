<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class RoleManagementController extends Controller
{
    /**
     * Role management is Super Admin only — Branch Managers should not be able to
     * grant themselves additional permissions or modify the role catalog.
     */
    private function authorizeSuperAdmin(): void
    {
        abort_unless(Auth::user()?->hasRole('Super Admin'), 403);
    }

    // ── Page ──────────────────────────────────────────────────────────────────
    public function index()
    {
        $this->authorizeSuperAdmin();

        return Inertia::render('SuperAdmin/Roles', [
            'roles'       => Role::with('permissions')->orderBy('name', 'asc')->get(),
            'permissions' => Permission::withCount('roles')->orderBy('name', 'asc')->get(),
            'users'       => User::with('roles')->orderBy('name', 'asc')->get(['id', 'name', 'email', 'created_at']),
        ]);
    }

    // ── Role CRUD ─────────────────────────────────────────────────────────────
    public function store(Request $request)
    {
        $this->authorizeSuperAdmin();
        $request->validate([
            'name'        => 'required|string|unique:roles,name',
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
        $this->authorizeSuperAdmin();
        $request->validate([
            'name'        => 'required|string|unique:roles,name,' . $role->id,
            'permissions' => 'array',
        ]);

        if ($role->name === 'Super Admin' && $request->name !== 'Super Admin') {
            return redirect()->back()->with('error', 'Cannot rename Super Admin role.');
        }

        DB::transaction(function () use ($request, $role) {
            $role->update(['name' => $request->name]);
            $role->syncPermissions($request->permissions ?? []);
        });

        return redirect()->back()->with('success', 'Role updated successfully.');
    }

    public function destroy(Role $role)
    {
        $this->authorizeSuperAdmin();

        if ($role->name === 'Super Admin') {
            return redirect()->back()->with('error', 'Cannot delete Super Admin role.');
        }

        $role->delete();

        return redirect()->back()->with('success', 'Role deleted successfully.');
    }

    // ── Permission CRUD ───────────────────────────────────────────────────────
    public function storePermission(Request $request)
    {
        $this->authorizeSuperAdmin();
        $request->validate([
            'name' => 'required|string|max:100|unique:permissions,name|regex:/^[a-z0-9\-]+$/',
        ], [
            'name.regex' => 'Permission name must be lowercase letters, numbers, and hyphens only.',
        ]);

        Permission::create(['name' => $request->name, 'guard_name' => 'web']);

        return redirect()->back()->with('success', "Permission '{$request->name}' created.");
    }

    public function updatePermission(Request $request, Permission $permission)
    {
        $this->authorizeSuperAdmin();
        $request->validate([
            'name' => 'required|string|max:100|unique:permissions,name,' . $permission->id . '|regex:/^[a-z0-9\-]+$/',
        ], [
            'name.regex' => 'Permission name must be lowercase letters, numbers, and hyphens only.',
        ]);

        $permission->update(['name' => $request->name]);

        return redirect()->back()->with('success', "Permission renamed to '{$request->name}'.");
    }

    public function destroyPermission(Permission $permission)
    {
        $this->authorizeSuperAdmin();

        $rolesCount = $permission->roles()->count();
        if ($rolesCount > 0) {
            return redirect()->back()->with('error', "Cannot delete '{$permission->name}' — it is assigned to {$rolesCount} role(s). Remove it from those roles first.");
        }

        $permission->delete();

        return redirect()->back()->with('success', "Permission '{$permission->name}' deleted.");
    }

    // ── User Role Management ──────────────────────────────────────────────────
    public function updateUserRoles(Request $request, User $user)
    {
        $this->authorizeSuperAdmin();
        $request->validate([
            'roles'   => 'array',
            'roles.*' => 'string|exists:roles,name',
        ]);

        // Never strip Super Admin from their own account
        if ($user->id === Auth::id() && !in_array('Super Admin', $request->roles ?? [])) {
            return redirect()->back()->with('error', 'You cannot remove the Super Admin role from your own account.');
        }

        $user->syncRoles($request->roles ?? []);

        return redirect()->back()->with('success', "Roles updated for {$user->name}.");
    }

    public function destroyUser(User $user)
    {
        $this->authorizeSuperAdmin();

        if ($user->id === Auth::id()) {
            return redirect()->back()->with('error', 'You cannot delete your own account.');
        }

        $user->delete();

        return redirect()->back()->with('success', "{$user->name} has been removed from the system.");
    }
}
