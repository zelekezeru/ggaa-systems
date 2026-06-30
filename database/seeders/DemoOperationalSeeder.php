<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Client;
use App\Models\User;
use App\Models\StaffUser;
use App\Models\BankAccount;
use App\Models\BankAccountBalance;
use App\Models\MonthlyLedger;
use App\Models\PurchaseReceipt;
use App\Models\ServiceInvoice;
use App\Models\ServiceInvoicePayment;
use App\Models\TaskTemplate;
use App\Models\Task;
use App\Models\TeamProject;
use App\Models\TeamProjectMember;
use App\Models\TeamProjectTodo;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoOperationalSeeder extends Seeder
{
    public function run(): void
    {
        // ── 1. Create canonical Demo Accounts from README ─────────────────────
        $branch = Branch::where('name', 'Central Ethiopia Branch')->first();
        if (!$branch) {
            $branch = Branch::firstOrCreate(
                ['name' => 'Hawassa Branch'],
                ['location' => 'Sidama Region, Hawassa', 'is_active' => true]
            );
        }

        // Branch Manager
        $manager = User::updateOrCreate(
            ['email' => 'manager@ggaa.com'],
            [
                'name'                 => 'Mulugeta Alula (Manager)',
                'password'             => Hash::make('ggaa@password'),
                'must_change_password' => false,
                'branch_id'            => $branch->id,
                'email_verified_at'    => now(),
            ]
        );
        $manager->syncRoles(['Branch Manager']);
        $branch->update(['manager_id' => $manager->id]);

        StaffUser::updateOrCreate(
            ['user_id' => $manager->id],
            ['position' => 'manager', 'employment_type' => 'full_time', 'is_active' => true]
        );

        // Employee
        $employee = User::updateOrCreate(
            ['email' => 'employee@ggaa.com'],
            [
                'name'                 => 'Kidus Yohannes (Accountant)',
                'password'             => Hash::make('ggaa@password'),
                'must_change_password' => false,
                'branch_id'            => $branch->id,
                'email_verified_at'    => now(),
            ]
        );
        $employee->syncRoles(['Employee']);

        StaffUser::updateOrCreate(
            ['user_id' => $employee->id],
            ['position' => 'employee', 'employment_type' => 'full_time', 'is_active' => true]
        );

        // Finance Admin
        $finance = User::updateOrCreate(
            ['email' => 'finance@ggaa.com'],
            [
                'name'                 => 'Tewodros Assefa (Finance)',
                'password'             => Hash::make('ggaa@password'),
                'must_change_password' => false,
                'email_verified_at'    => now(),
            ]
        );
        $finance->syncRoles(['Finance Admin']);

        StaffUser::updateOrCreate(
            ['user_id' => $finance->id],
            ['position' => 'finance', 'employment_type' => 'full_time', 'is_active' => true]
        );

        // ── 2. Setup Client Assignments ──────────────────────────────────────
        // Find or create first client: Tuma Plastic
        $client = Client::where('company_name', 'Tuma Plastic')->first();
        if (!$client) {
            $client = Client::create([
                'company_name'        => 'Tuma Plastic',
                'tin_number'          => '0054600420',
                'region'              => 'Central Ethiopia',
                'main_office'         => 'Hosaina',
                'phone'               => '0925521111',
                'email'               => 'tumaplastic@gmail.com',
                'sector'              => 'Manufacturing',
                'branch_id'           => $branch->id,
                'status'              => 'Active',
                'complexity_score'    => 2,
            ]);
        }

        // Link client portal demo user
        $clientPortalUser = User::where('email', 'client@ggaa.com')->first();
        if ($clientPortalUser) {
            $clientPortalUser->update(['client_id' => $client->id]);
        }

        // Assign employee to client
        $client->update(['assigned_employee_id' => $employee->id]);

        // ── 3. Bank Accounts ──────────────────────────────────────────────────
        $cbe = BankAccount::firstOrCreate(
            ['client_id' => $client->id, 'account_number' => '1000123456789'],
            ['bank_name' => 'Commercial Bank of Ethiopia (CBE)', 'account_type' => 'current', 'is_active' => true]
        );

        $awash = BankAccount::firstOrCreate(
            ['client_id' => $client->id, 'account_number' => '0132098765432'],
            ['bank_name' => 'Awash Bank', 'account_type' => 'current', 'is_active' => true]
        );

        // ── 4. Full Year Ledger Data (Ethiopian Year 2018) ────────────────────
        // Hamle 2017, Nehase 2017, then Meskeram 2018 -> Sene 2018 (12 months)
        $months = [
            ['eth_year' => 2017, 'eth_month' => 'Hamle',     'status' => 'verified',  'cash' => 120000, 'man' => 25000, 'beg' => 80000,  'pur' => 60000,  'end' => 90000,  'cbe' => 450000, 'awa' => 120000],
            ['eth_year' => 2017, 'eth_month' => 'Nehase',    'status' => 'verified',  'cash' => 135000, 'man' => 30000, 'beg' => 90000,  'pur' => 70000,  'end' => 110000, 'cbe' => 470000, 'awa' => 135000],
            ['eth_year' => 2018, 'eth_month' => 'Meskeram',  'status' => 'verified',  'cash' => 150000, 'man' => 25000, 'beg' => 110000, 'pur' => 80000,  'end' => 120000, 'cbe' => 500000, 'awa' => 140000],
            ['eth_year' => 2018, 'eth_month' => 'Tiqimt',    'status' => 'verified',  'cash' => 145000, 'man' => 35000, 'beg' => 120000, 'pur' => 75000,  'end' => 130000, 'cbe' => 510000, 'awa' => 145000],
            ['eth_year' => 2018, 'eth_month' => 'Hidar',     'status' => 'verified',  'cash' => 160000, 'man' => 40000, 'beg' => 130000, 'pur' => 90000,  'end' => 140000, 'cbe' => 540000, 'awa' => 160000],
            ['eth_year' => 2018, 'eth_month' => 'Tahisas',   'status' => 'verified',  'cash' => 175000, 'man' => 50000, 'beg' => 140000, 'pur' => 100000, 'end' => 150000, 'cbe' => 575000, 'awa' => 185000],
            ['eth_year' => 2018, 'eth_month' => 'Tirr',      'status' => 'verified',  'cash' => 130050, 'man' => 20000, 'beg' => 150000, 'pur' => 50000,  'end' => 145000, 'cbe' => 580000, 'awa' => 180000],
            ['eth_year' => 2018, 'eth_month' => 'Yeketit',   'status' => 'verified',  'cash' => 140000, 'man' => 25000, 'beg' => 145000, 'pur' => 65005,  'end' => 140000, 'cbe' => 610000, 'awa' => 195000],
            ['eth_year' => 2018, 'eth_month' => 'Megabit',   'status' => 'verified',  'cash' => 155000, 'man' => 30000, 'beg' => 140000, 'pur' => 85000,  'end' => 135000, 'cbe' => 625000, 'awa' => 210000],
            ['eth_year' => 2018, 'eth_month' => 'Miyaziya',  'status' => 'submitted', 'cash' => 165000, 'man' => 35000, 'beg' => 135000, 'pur' => 95000,  'end' => 130000, 'cbe' => 645000, 'awa' => 220000],
            ['eth_year' => 2018, 'eth_month' => 'Ginbot',    'status' => 'submitted', 'cash' => 170000, 'man' => 40000, 'beg' => 130000, 'pur' => 105000, 'end' => 125000, 'cbe' => 660000, 'awa' => 235000],
            ['eth_year' => 2018, 'eth_month' => 'Sene',      'status' => 'draft',     'cash' => 180000, 'man' => 45000, 'beg' => 125000, 'pur' => 110000, 'end' => 120000, 'cbe' => 685000, 'awa' => 250000],
        ];

        $startInvoiceNo = 1000;
        foreach ($months as $m) {
            $ledger = MonthlyLedger::updateOrCreate(
                [
                    'client_id' => $client->id,
                    'eth_year'  => $m['eth_year'],
                    'eth_month' => $m['eth_month']
                ],
                [
                    'status'                      => $m['status'],
                    'cash_machine_sales'          => $m['cash'],
                    'manual_sales'                => $m['man'],
                    'cash_machine_start_number'   => (string) $startInvoiceNo,
                    'cash_machine_end_number'     => (string) ($startInvoiceNo + 200),
                    'manual_receipt_start_number' => (string) ($startInvoiceNo + 1000),
                    'manual_receipt_end_number'   => (string) ($startInvoiceNo + 1050),
                    'beginning_inventory'         => $m['beg'],
                    'purchases'                   => $m['pur'],
                    'ending_inventory'            => $m['end'],
                    'inventory_items_start'       => 1000.0,
                    'inventory_items_end'         => 1200.0,
                    'inventory_sold_quantity'     => 450.0,
                    'salary_expense'              => 20000.0,
                    'pension_expense'             => 2200.0,
                    'printing_expense'            => 800.0,
                    'shed_rent'                   => 12000.0,
                    'stationery_expense'          => 500.0,
                    'office_rent_expense'         => 10000.0,
                    'transport_expense'           => 4000.0,
                    'eeu_expense'                 => 1800.0,
                    'maintenance_expense'         => 2500.0,
                    'advertising_expense'         => 1500.0,
                    'depreciation_expense'        => 3000.0,
                    'tax_rate'                    => 35.0,
                    'submitted_by'                => $employee->id,
                    'submitted_at'                => now()->subDays(10),
                    'verified_by'                 => $m['status'] === 'verified' ? $manager->id : null,
                    'verified_at'                 => $m['status'] === 'verified' ? now()->subDays(8) : null,
                ]
            );

            // Seed Bank balances
            BankAccountBalance::updateOrCreate(
                ['bank_account_id' => $cbe->id, 'monthly_ledger_id' => $ledger->id],
                ['balance' => $m['cbe'], 'loan_amount' => 50000.0]
            );

            BankAccountBalance::updateOrCreate(
                ['bank_account_id' => $awash->id, 'monthly_ledger_id' => $ledger->id],
                ['balance' => $m['awa']]
            );

            $startInvoiceNo += 205;
        }

        // ── 5. Purchase Receipts (for Sene 2018 carry-overs) ─────────────────
        PurchaseReceipt::updateOrCreate(
            ['client_id' => $client->id, 'invoice_number' => 'REC-2018-081'],
            [
                'eth_month'          => 'Sene',
                'eth_year'           => 2018,
                'supplier_name'      => 'Addis Chemicals Ltd',
                'receipt_date'       => Carbon::now()->subDays(5)->toDateString(),
                'description'        => 'Raw chemicals purchases for plastics manufacturing',
                'expense_category'   => 'raw_material',
                'amount_before_vat'  => 60000.00,
                'vat_amount'         => 9000.00,
                'has_vat_receipt'    => true,
                'captured_by'        => $employee->id,
            ]
        );

        PurchaseReceipt::updateOrCreate(
            ['client_id' => $client->id, 'invoice_number' => 'REC-2018-082'],
            [
                'eth_month'          => 'Sene',
                'eth_year'           => 2018,
                'supplier_name'      => 'Hawassa Packagings',
                'receipt_date'       => Carbon::now()->subDays(4)->toDateString(),
                'description'        => 'Master plastic containers packaging materials',
                'expense_category'   => 'raw_material',
                'amount_before_vat'  => 50000.00,
                'vat_amount'         => 7500.00,
                'has_vat_receipt'    => true,
                'captured_by'        => $employee->id,
            ]
        );

        // ── 6. Service Invoices & Payments ───────────────────────────────────
        $inv1 = ServiceInvoice::updateOrCreate(
            ['invoice_number' => 'INV-2026-0001'],
            [
                'client_id'    => $client->id,
                'period_start' => Carbon::now()->subMonths(12)->startOfMonth(),
                'period_end'   => Carbon::now()->subMonths(6)->endOfMonth(),
                'amount'       => 15000.00,
                'description'  => 'Half-year ledger auditing and tax filing retainer',
                'status'       => 'paid',
                'issued_at'    => Carbon::now()->subMonths(6),
                'due_date'     => Carbon::now()->subMonths(5)->endOfMonth(),
                'paid_at'      => Carbon::now()->subMonths(5),
                'created_by'   => $finance->id,
            ]
        );

        ServiceInvoicePayment::updateOrCreate(
            ['service_invoice_id' => $inv1->id, 'reference' => 'CBE-TXN-820124'],
            [
                'amount'         => 15000.00,
                'payment_method' => 'bank_transfer',
                'status'         => 'Completed',
                'paid_at'        => Carbon::now()->subMonths(5)->toDateString(),
                'recorded_by'    => $finance->id,
                'approved_by'    => $finance->id,
                'approved_at'    => Carbon::now()->subMonths(5),
            ]
        );

        $inv2 = ServiceInvoice::updateOrCreate(
            ['invoice_number' => 'INV-2026-0002'],
            [
                'client_id'    => $client->id,
                'period_start' => Carbon::now()->subMonths(5)->startOfMonth(),
                'period_end'   => Carbon::now()->subMonths(1)->endOfMonth(),
                'amount'       => 10000.00,
                'description'  => 'Monthly bookkeeping service retainer',
                'status'       => 'paid',
                'issued_at'    => Carbon::now()->subMonths(1),
                'due_date'     => Carbon::now()->endOfMonth(),
                'paid_at'      => Carbon::now()->subDays(2),
                'created_by'   => $finance->id,
            ]
        );

        ServiceInvoicePayment::updateOrCreate(
            ['service_invoice_id' => $inv2->id, 'reference' => 'CBE-TXN-90231'],
            [
                'amount'         => 10000.00,
                'payment_method' => 'bank_transfer',
                'status'         => 'Completed',
                'paid_at'        => Carbon::now()->subDays(2)->toDateString(),
                'recorded_by'    => $finance->id,
                'approved_by'    => $finance->id,
                'approved_at'    => Carbon::now()->subDays(2),
            ]
        );

        // Unpaid outstanding invoice
        ServiceInvoice::updateOrCreate(
            ['invoice_number' => 'INV-2026-0003'],
            [
                'client_id'    => $client->id,
                'period_start' => Carbon::now()->startOfMonth(),
                'period_end'   => Carbon::now()->endOfMonth(),
                'amount'       => 2500.00,
                'description'  => 'Urgent tax audit support advisory',
                'status'       => 'sent',
                'issued_at'    => Carbon::now()->subDays(1),
                'due_date'     => Carbon::now()->addDays(14)->toDateString(),
                'created_by'   => $finance->id,
            ]
        );

        // ── 7. Task Templates & Operational Tasks ───────────────────────────
        $tplVat = TaskTemplate::firstOrCreate(
            ['name' => 'VAT E-Filing'],
            ['frequency' => 'Monthly', 'due_date_offset' => 20, 'requires_document' => true]
        );

        $tplBook = TaskTemplate::firstOrCreate(
            ['name' => 'Monthly Bookkeeping'],
            ['frequency' => 'Monthly', 'due_date_offset' => 30, 'requires_document' => false]
        );

        $tplPen = TaskTemplate::firstOrCreate(
            ['name' => 'Pension Submission'],
            ['frequency' => 'Monthly', 'due_date_offset' => 15, 'requires_document' => true]
        );

        // Task 1: Overdue task (🔴 Risk)
        Task::updateOrCreate(
            ['client_id' => $client->id, 'task_template_id' => $tplPen->id, 'due_date' => Carbon::now()->subDays(10)->toDateString()],
            [
                'assigned_user_id' => $employee->id,
                'status'           => 'To Do',
                'notes'            => 'Urgent pension calculations for employees have not been uploaded.',
            ]
        );

        // Task 2: Active Todo task (🟡 Risk / Active)
        Task::updateOrCreate(
            ['client_id' => $client->id, 'task_template_id' => $tplVat->id, 'due_date' => Carbon::now()->addDays(2)->toDateString()],
            [
                'assigned_user_id' => $employee->id,
                'status'           => 'To Do',
                'notes'            => 'Meskeram VAT calculations need to be compiled.',
            ]
        );

        // Task 3: Finished Task (🟢 Done)
        Task::updateOrCreate(
            ['client_id' => $client->id, 'task_template_id' => $tplBook->id, 'due_date' => Carbon::now()->subDays(15)->toDateString()],
            [
                'assigned_user_id' => $employee->id,
                'status'           => 'Done',
                'completed_at'     => Carbon::now()->subDays(15),
                'notes'            => 'Initial bank and cash ledger entries complete.',
            ]
        );

        // ── 8. Team Projects & Todos ─────────────────────────────────────────
        $project = TeamProject::updateOrCreate(
            ['client_id' => $client->id, 'title' => 'Tuma Plastic Fiscal Audit Prep'],
            [
                'branch_id'      => $branch->id,
                'team_leader_id' => $manager->id,
                'status'         => 'in_progress',
                'start_date'     => Carbon::now()->subDays(10)->toDateString(),
                'due_date'       => Carbon::now()->addDays(20)->toDateString(),
                'created_by'     => $manager->id,
                'description'    => 'Inter-departmental team project to clean and organize Tuma Plastic books for regional tax inspection.',
            ]
        );

        // Add members
        TeamProjectMember::firstOrCreate(
            ['team_project_id' => $project->id, 'user_id' => $manager->id],
            ['role_in_team' => 'leader']
        );

        TeamProjectMember::firstOrCreate(
            ['team_project_id' => $project->id, 'user_id' => $employee->id],
            ['role_in_team' => 'member']
        );

        TeamProjectMember::firstOrCreate(
            ['team_project_id' => $project->id, 'user_id' => $finance->id],
            ['role_in_team' => 'member']
        );

        // Add project Todos (2 done, 1 pending = 67% progress)
        TeamProjectTodo::updateOrCreate(
            ['team_project_id' => $project->id, 'title' => 'Verify VAT ledger entries'],
            ['status' => 'done', 'assigned_to' => $employee->id, 'created_by' => $manager->id, 'order_index' => 1]
        );

        TeamProjectTodo::updateOrCreate(
            ['team_project_id' => $project->id, 'title' => 'Upload registration documents to secure Vault'],
            ['status' => 'done', 'assigned_to' => $manager->id, 'created_by' => $manager->id, 'order_index' => 2]
        );

        TeamProjectTodo::updateOrCreate(
            ['team_project_id' => $project->id, 'title' => 'Reconcile CBE bank balance transactions for Sene'],
            ['status' => 'todo', 'assigned_to' => $finance->id, 'created_by' => $manager->id, 'order_index' => 3]
        );
    }
}
