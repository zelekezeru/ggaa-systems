<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Align staff positions with the GGAA organizational chart.
 *
 * The org chart hierarchy:
 *   GM → (Operation Manager | Finance & Admin | AA Branch Manager)
 *   Operation Manager → Tax Report TL, Customer Follow-up TL, Pre-Audit TL, File Management
 *   Finance & Admin   → HR/Purchase/Cashier, Cleaning, Technical Support
 *
 * We convert the rigid `position` enum into a flexible string column so the
 * catalogue can grow without future enum migrations, then remap the legacy
 * values onto the new org-chart taxonomy (preserving each person's authority).
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Relax the enum into a string column (MySQL-safe, no dbal needed).
        DB::statement("ALTER TABLE staff_users MODIFY position VARCHAR(50) NOT NULL DEFAULT 'junior_accountant'");

        // 2. Remap legacy enum values → org-chart positions (authority preserved).
        $map = [
            'admin'       => 'general_manager',   // system god → GM (Super Admin)
            'manager'     => 'branch_manager',    // generic manager → Branch Manager
            'finance'     => 'finance_admin',
            'team_leader' => 'team_leader',
            'employee'    => 'junior_accountant', // default rank-and-file accountant
            'other'       => 'junior_accountant',
        ];

        foreach ($map as $old => $new) {
            DB::table('staff_users')->where('position', $old)->update(['position' => $new]);
        }

        // 3. Add an operational team specialization (the team-leader branches).
        if (! Schema::hasColumn('staff_users', 'team')) {
            Schema::table('staff_users', function ($table) {
                $table->string('team', 40)->nullable()->after('position_title')
                    ->comment('Operational unit: tax_report, customer_followup, pre_audit, file_management, general');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('staff_users', 'team')) {
            Schema::table('staff_users', function ($table) {
                $table->dropColumn('team');
            });
        }

        // Best-effort reverse remap, then restore the original enum.
        $reverse = [
            'general_manager'     => 'admin',
            'operation_manager'   => 'manager',
            'branch_manager'      => 'manager',
            'finance_admin'       => 'finance',
            'team_leader'         => 'team_leader',
            'senior_accountant'   => 'employee',
            'junior_accountant'   => 'employee',
            'file_management'     => 'employee',
            'hr_purchase_cashier' => 'finance',
            'cleaning'            => 'other',
            'technical_support'   => 'other',
        ];

        foreach ($reverse as $new => $old) {
            DB::table('staff_users')->where('position', $new)->update(['position' => $old]);
        }

        DB::statement("ALTER TABLE staff_users MODIFY position ENUM('employee','manager','team_leader','admin','finance','other') NOT NULL DEFAULT 'employee'");
    }
};
