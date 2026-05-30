<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StaffUser extends Model
{
    protected $guarded = [];

    protected $casts = [
        'hire_date' => 'date',
        'is_active' => 'boolean',
    ];

    /**
     * Staff positions mirror the GGAA organizational chart, grouped by tier.
     * The key is stored on the row; the label is what the UI displays.
     */
    public const POSITIONS = [
        // ── Executive ──
        'general_manager'     => 'General Manager (GM)',
        // ── Management ──
        'operation_manager'   => 'Operation Manager',
        'finance_admin'       => 'Finance & Admin',
        'branch_manager'      => 'Branch Manager',
        // ── Operations team leaders ──
        'team_leader'         => 'Team Leader',
        // ── Operations staff ──
        'senior_accountant'   => 'Senior Accountant',
        'junior_accountant'   => 'Junior Accountant',
        'file_management'     => 'File Management',
        // ── Finance & Admin support ──
        'hr_purchase_cashier' => 'HR, Purchase & Cashier',
        'technical_support'   => 'Technical Support',
        'cleaning'            => 'Cleaning & Facilities',
    ];

    /**
     * Operational team specializations (the team-leader branches in the chart).
     * Used to tag Team Leaders / their members with their functional unit.
     */
    public const TEAMS = [
        'tax_report'       => 'Tax Report',
        'customer_followup' => 'Customer Follow-up',
        'pre_audit'        => 'Pre-Audit',
        'file_management'  => 'File Management',
        'general'          => 'General',
    ];

    /**
     * Grouping used purely for display/ordering in the org directory.
     */
    public const POSITION_TIERS = [
        'general_manager'     => 'Executive',
        'operation_manager'   => 'Management',
        'finance_admin'       => 'Management',
        'branch_manager'      => 'Management',
        'team_leader'         => 'Team Leaders',
        'senior_accountant'   => 'Operations',
        'junior_accountant'   => 'Operations',
        'file_management'     => 'Operations',
        'hr_purchase_cashier' => 'Finance & Admin Support',
        'technical_support'   => 'Finance & Admin Support',
        'cleaning'            => 'Finance & Admin Support',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
