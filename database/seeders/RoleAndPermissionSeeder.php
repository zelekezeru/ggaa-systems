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

        // 1. Create Roles — aligned with the GGAA org chart.
        // GM = Super Admin (top authority). Operation Manager oversees the
        // operational teams (tax report / customer follow-up / pre-audit / files).
        $superAdmin    = Role::firstOrCreate(['name' => 'Super Admin']);
        $operationMgr  = Role::firstOrCreate(['name' => 'Operation Manager']);
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
            'manage billing',
            'approve payments',
            'reject payments',
            'generate expected payments',
            'submit draft payments',
            // Client management permissions
            'manage-clients',
            'view-clients',
            // Service type permissions
            'manage-service-types',
            // Task assignment permissions
            'assign-tasks',
            'unassign-tasks',
            // Team project permissions
            'manage team projects',
            'view team projects',
            'lead team project',
            'participate team project',
            // Financial ledger (client's own monthly P&L) permissions
            'view ledger index',
            'enter ledger data',
            'submit ledger',
            'verify ledger',
            'view client ledger reports',
            'download ledger reports',
            // Service invoicing (firm's billing of clients) permissions
            'view ledger progress',
            'manage service invoices',
            'send invoice',
            'cancel invoice',
            'download invoice report',
            // Payment workflow permissions
            'approve invoice payments',
            'schedule invoice payments',
            'reject invoice payments',
            'view all payments',
            // Client-side payment portal permissions
            'view own invoices',
            'view own payments',
            'submit invoice payment',
            'upload payment receipt',
            'download own invoices',
            // Staff performance evaluation permissions
            'view evaluations',
            'manage evaluations',
            'manage evaluation metrics',
            'view own evaluation',
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
            'view-user',
            'assign-tasks',
            'unassign-tasks',
            'manage team projects',
            'view team projects',
            'view ledger index',
            'enter ledger data',
            'submit ledger',
            'verify ledger',
            'view client ledger reports',
            'download ledger reports',
            'view evaluations',
            'manage evaluations',
            'view own evaluation',
        ]);

        // Operation Manager — firm-wide operational oversight of the teams:
        // tasks, team projects, staff visibility, and performance evaluations.
        $operationMgr->syncPermissions([
            'view all branches',
            'view branch clients',
            'view-clients',
            'view-user',
            'assign-tasks',
            'unassign-tasks',
            'manage team projects',
            'view team projects',
            'view ledger index',
            'enter ledger data',
            'verify ledger',
            'view ledger progress',
            'view client ledger reports',
            'download ledger reports',
            'view evaluations',
            'manage evaluations',
            'manage evaluation metrics',
            'view own evaluation',
        ]);

        $teamLeader->syncPermissions([
            'view assigned clients',
            'update task status',
            'upload client documents',
            'view team projects',
            'lead team project',
            'participate team project',
            'send direct messages',
            'view evaluations',
            'manage evaluations',
            'view own evaluation',
        ]);

        $employee->syncPermissions([
            'view assigned clients',
            'view-clients',
            'update task status',
            'upload client documents',
            'view team projects',
            'participate team project',
            'view ledger index',
            'enter ledger data',
            'submit ledger',
            'view client ledger reports',
            'download ledger reports',
            'view own evaluation',
        ]);

        $financeAdmin->syncPermissions([
            'view finance billing',
            'send payment reminders',
            'record payments',
            'manage billing',
            'approve payments',
            'reject payments',
            'generate expected payments',
            'submit draft payments',
            'view team projects',
            'participate team project',
            'view ledger progress',
            'view client ledger reports',
            'download ledger reports',
            'manage service invoices',
            'send invoice',
            'cancel invoice',
            'download invoice report',
            'approve invoice payments',
            'schedule invoice payments',
            'reject invoice payments',
            'view all payments',
            'view own evaluation',
        ]);

        $clientUser->syncPermissions([
            'view own portal',
            'view own agreements',
            'download own reports',
            'send direct messages',
            'view client ledger reports',
            'download ledger reports',
            'view own invoices',
            'view own payments',
            'submit invoice payment',
            'upload payment receipt',
            'download own invoices',
        ]);
    }
}
