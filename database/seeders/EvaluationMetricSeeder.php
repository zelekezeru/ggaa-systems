<?php

namespace Database\Seeders;

use App\Models\EvaluationMetric;
use Illuminate\Database\Seeder;

/**
 * Industry-standard performance metrics for an Ethiopian accounting & tax
 * advisory firm, organized by category and targeted by role.
 *
 * Weights are suggested defaults (the system normalizes them to 100%), tuned
 * so that for any given role the relevant metrics sum to roughly 100.
 */
class EvaluationMetricSeeder extends Seeder
{
    public function run(): void
    {
        $metrics = [
            // ───────────────── Common to ALL staff (applies_to_roles = null) ─────────────────
            [
                'key' => 'task_on_time_delivery', 'name' => 'On-time Task Delivery',
                'description' => 'Share of assigned engagement tasks completed on or before the due date.',
                'category' => 'task_performance', 'source' => 'auto',
                'computation_key' => 'task_on_time_rate', 'applies_to_roles' => null,
                'default_weight' => 18, 'sort_order' => 10,
            ],
            [
                'key' => 'task_weighted_quality', 'name' => 'Weighted Task Performance',
                'description' => 'Complexity-weighted task score — heavier client files count for more.',
                'category' => 'task_performance', 'source' => 'auto',
                'computation_key' => 'task_weighted_score', 'applies_to_roles' => null,
                'default_weight' => 12, 'sort_order' => 11,
            ],
            [
                'key' => 'daily_ops_completion', 'name' => 'Daily Operations Completion',
                'description' => 'Completion rate of daily operational tasks (errands, filings, client visits).',
                'category' => 'daily_task', 'source' => 'auto',
                'computation_key' => 'daily_task_completion', 'applies_to_roles' => null,
                'default_weight' => 10, 'sort_order' => 20,
            ],
            [
                'key' => 'professional_ethics', 'name' => 'Professional Ethics & Integrity',
                'description' => 'Adherence to confidentiality, independence and the ethical code expected of accountants.',
                'category' => 'professionalism', 'source' => 'manual',
                'computation_key' => null, 'applies_to_roles' => null,
                'default_weight' => 12, 'sort_order' => 30,
            ],
            [
                'key' => 'attendance_punctuality', 'name' => 'Attendance & Punctuality',
                'description' => 'Reliability of attendance, timeliness and respect for working hours.',
                'category' => 'attendance', 'source' => 'manual',
                'computation_key' => null, 'applies_to_roles' => null,
                'default_weight' => 8, 'sort_order' => 31,
            ],
            [
                'key' => 'supervisor_review', 'name' => 'Team Leader / Manager Review',
                'description' => 'Direct supervisor’s holistic rating of initiative, reliability and quality of work.',
                'category' => 'manager_review', 'source' => 'manual',
                'computation_key' => null, 'applies_to_roles' => null,
                'default_weight' => 12, 'sort_order' => 32,
            ],
            [
                'key' => 'teamwork_collaboration', 'name' => 'Teamwork & Collaboration',
                'description' => 'Contribution to team projects, knowledge sharing and peer support.',
                'category' => 'team_project', 'source' => 'manual',
                'computation_key' => null, 'applies_to_roles' => null,
                'default_weight' => 8, 'sort_order' => 33,
            ],

            // ───────────────── Accountants (Employee role) ─────────────────
            [
                'key' => 'ledger_accuracy', 'name' => 'Ledger & Bookkeeping Accuracy',
                'description' => 'Accuracy and completeness of monthly ledger entries with minimal correction rework.',
                'category' => 'quality_compliance', 'source' => 'manual',
                'computation_key' => null, 'applies_to_roles' => ['Employee'],
                'default_weight' => 14, 'sort_order' => 40,
            ],
            [
                'key' => 'tax_compliance_deadlines', 'name' => 'Tax & ERCA Compliance',
                'description' => 'Meeting VAT, withholding and profit-tax filing deadlines per ERCA requirements.',
                'category' => 'quality_compliance', 'source' => 'manual',
                'computation_key' => null, 'applies_to_roles' => ['Employee', 'Team Leader'],
                'default_weight' => 12, 'sort_order' => 41,
            ],
            [
                'key' => 'documentation_quality', 'name' => 'Documentation & Filing Quality',
                'description' => 'Quality, organization and retrievability of client documents and working papers.',
                'category' => 'quality_compliance', 'source' => 'manual',
                'computation_key' => null, 'applies_to_roles' => ['Employee'],
                'default_weight' => 8, 'sort_order' => 42,
            ],
            [
                'key' => 'learning_development', 'name' => 'Learning & Development',
                'description' => 'Growth in technical skill, IFRS/tax knowledge and uptake of training.',
                'category' => 'leadership', 'source' => 'manual',
                'computation_key' => null, 'applies_to_roles' => ['Employee'],
                'default_weight' => 6, 'sort_order' => 43,
            ],

            // ───────────────── Finance & Admin ─────────────────
            [
                'key' => 'billing_collections', 'name' => 'Billing & Collections Effectiveness',
                'description' => 'Timely invoicing, follow-up and collection of receivables from clients.',
                'category' => 'quality_compliance', 'source' => 'manual',
                'computation_key' => null, 'applies_to_roles' => ['Finance Admin'],
                'default_weight' => 16, 'sort_order' => 50,
            ],
            [
                'key' => 'cash_handling_integrity', 'name' => 'Cash Handling & Payment Integrity',
                'description' => 'Accuracy and integrity of cash handling, payment processing and reconciliations.',
                'category' => 'quality_compliance', 'source' => 'manual',
                'computation_key' => null, 'applies_to_roles' => ['Finance Admin'],
                'default_weight' => 14, 'sort_order' => 51,
            ],

            // ───────────────── Team Leaders ─────────────────
            [
                'key' => 'team_project_delivery', 'name' => 'Team Project Delivery',
                'description' => 'On-time, on-scope delivery of the team’s projects and todo completion.',
                'category' => 'team_project', 'source' => 'auto',
                'computation_key' => 'team_project_contribution', 'applies_to_roles' => ['Team Leader', 'Operation Manager'],
                'default_weight' => 14, 'sort_order' => 60,
            ],
            [
                'key' => 'mentorship_review_quality', 'name' => 'Mentorship & Review Quality',
                'description' => 'Effectiveness in reviewing junior work, coaching and raising team standards.',
                'category' => 'leadership', 'source' => 'manual',
                'computation_key' => null, 'applies_to_roles' => ['Team Leader'],
                'default_weight' => 10, 'sort_order' => 61,
            ],

            // ───────────────── Managers (Branch / Operation) ─────────────────
            [
                'key' => 'client_satisfaction', 'name' => 'Client Satisfaction',
                'description' => 'Measured client satisfaction, retention and complaint resolution for the portfolio.',
                'category' => 'client_satisfaction', 'source' => 'manual',
                'computation_key' => null, 'applies_to_roles' => ['Branch Manager', 'Operation Manager', 'Team Leader'],
                'default_weight' => 14, 'sort_order' => 70,
            ],
            [
                'key' => 'operations_kpi', 'name' => 'Operations / Branch KPI Achievement',
                'description' => 'Achievement of operational targets: throughput, capacity utilization and SLA adherence.',
                'category' => 'manager_review', 'source' => 'manual',
                'computation_key' => null, 'applies_to_roles' => ['Branch Manager', 'Operation Manager'],
                'default_weight' => 16, 'sort_order' => 71,
            ],
            [
                'key' => 'staff_development_leadership', 'name' => 'Staff Development & Leadership',
                'description' => 'Developing the team, managing performance and fostering a healthy work culture.',
                'category' => 'leadership', 'source' => 'manual',
                'computation_key' => null, 'applies_to_roles' => ['Branch Manager', 'Operation Manager'],
                'default_weight' => 12, 'sort_order' => 72,
            ],
        ];

        foreach ($metrics as $m) {
            EvaluationMetric::updateOrCreate(
                ['key' => $m['key']],
                array_merge($m, ['is_system' => true, 'is_active' => true])
            );
        }
    }
}
