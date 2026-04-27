<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleAndPermissionSeeder extends Seeder
{
    public function run()
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // 1. Create Roles
        $superAdmin = Role::firstOrCreate(['name' => 'Super Admin']);
        $branchManager = Role::firstOrCreate(['name' => 'Branch Manager']);
        $employee = Role::firstOrCreate(['name' => 'Employee']);
        $clientUser = Role::firstOrCreate(['name' => 'Client']);

        // 2. Create foundational permissions
        $permissions = [
            'manage-user',
            'view-user',
            'edit-user',
            'delete-user',
            'manage-branch',
            'view-branch',
            'edit-branch',
            'delete-branch',
            'view all branches',
            'manage system settings',
            'view branch clients',
            'view assigned clients',
            'update task status',
            'upload client documents',
            'view own portal',
            'view own agreements',
            'download own reports',
            'send direct messages'
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // 3. Assign Permissions to Roles
        $superAdmin->givePermissionTo(Permission::all()); // God mode
        
        $branchManager->givePermissionTo([
            'view branch clients',
        ]);
        
        $employee->givePermissionTo([
            'view assigned clients',
            'update task status',
            'upload client documents'
        ]);

        $clientUser->givePermissionTo([
            'view own portal',
            'view own agreements',
            'download own reports',
            'send direct messages'
        ]);
    }
}
