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
        $superAdmin    = Role::firstOrCreate(['name' => 'Super Admin']);
        $branchManager = Role::firstOrCreate(['name' => 'Branch Manager']);
        $teamLeader    = Role::firstOrCreate(['name' => 'Team Leader']);
        $employee      = Role::firstOrCreate(['name' => 'Employee']);
        $financeAdmin  = Role::firstOrCreate(['name' => 'Finance Admin']);
        $clientUser    = Role::firstOrCreate(['name' => 'Client']);

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
            'send direct messages',
            'view finance billing',
            'send payment reminders',
            'record payments',
            // Client management permissions
            'manage-clients',
            'view-clients',
            // Service type permissions
            'manage-service-types',
            // Task assignment permissions
            'assign-tasks',
            'unassign-tasks',
            // Additional finance permissions
            'manage-billing',
            'send-payment-reminders',
            // Team project permissions
            'manage team projects',
            'view team projects',
            'lead team project',
            'participate team project',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Clear any cached permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // 3. Assign Permissions to Roles
        $superAdmin->syncPermissions(Permission::all()); // God mode

        $branchManager->syncPermissions([
            'view branch clients',
            'manage-clients',
            'view-clients',
            'assign-tasks',
            'unassign-tasks',
            'manage team projects',
            'view team projects',
        ]);

        $teamLeader->syncPermissions([
            'view assigned clients',
            'update task status',
            'upload client documents',
            'view team projects',
            'lead team project',
            'participate team project',
            'send direct messages',
        ]);

        $employee->syncPermissions([
            'view assigned clients',
            'view-clients',
            'update task status',
            'upload client documents',
            'view team projects',
            'participate team project',
        ]);

        $financeAdmin->syncPermissions([
            'view finance billing',
            'send payment reminders',
            'record payments',
            'manage-billing',
            'send-payment-reminders',
            'view team projects',
            'participate team project',
        ]);

        $clientUser->syncPermissions([
            'view own portal',
            'view own agreements',
            'download own reports',
            'send direct messages',
        ]);
    }
}
