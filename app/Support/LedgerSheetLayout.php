<?php

namespace App\Support;

use App\Models\BankAccount;
use App\Models\Client;

/**
 * Single source of truth for the client ledger sheet layout (the real
 * accountant template): months across the columns, line-items down the rows.
 *
 * Both the template generator (LedgerSheetTemplateExport) and the transposed
 * reader (LedgerSheetSyncService) build off this so labels always line up.
 *
 * Fiscal year runs Hamle (month 11) of `fiscalStartYear` through Sene
 * (month 10) of the next year — 12 columns, exactly like the workbook
 * (Pagume is excluded).
 */
class LedgerSheetLayout
{
    /** field key => sheet label, in the sheet's order. */
    public const EXPENSE_FIELDS = [
        'salary_expense'             => 'Salary Expense',
        'pension_expense'            => 'Pension 11% Expense',
        'printing_expense'           => 'Printing Expense',
        'shed_rent'                  => 'Shed Rent',
        'stationery_expense'         => 'Stationery Expense',
        'office_rent_expense'        => 'Office Rent Expense',
        'transport_expense'          => 'Transportation Expense',
        'machine_fa_expense'         => 'Machine (FA)',
        'eeu_expense'                => 'EEU',
        'maintenance_expense'        => 'Maintenance',
        'advertising_expense'        => 'Advertizing Expense',
        'uniform_expense'            => 'Employee Uniform',
        'indirect_materials_expense' => 'Indirect Materials',
        'depreciation_expense'       => 'Deperciation Expense',
        'legal_fee_expense'          => 'Legal fee',
        'bank_interest_expense'      => 'Bank Interest Expense',
        'bank_service_charge'        => 'Bank Service Charge',
    ];

    /** The 4 bank movement rows: sheet label => BankAccountBalance column. */
    public const BANK_MOVEMENT_FIELDS = [
        'Bank Loan'        => 'loan_amount',
        'LC Margin Release'=> 'lc_margin_release',
        'Transfer'         => 'transfer_in',
        'Transfer revrsal' => 'transfer_reversal',
    ];

    /** Months 11,12 of the start year, then 1..10 of the next. 12 columns. */
    public static function monthColumns(int $fiscalStartYear): array
    {
        $cols = [
            ['eth_month' => 'Hamle',  'eth_year' => $fiscalStartYear],
            ['eth_month' => 'Nehase', 'eth_year' => $fiscalStartYear],
        ];
        foreach (['Meskeram', 'Tiqimt', 'Hidar', 'Tahisas', 'Tirr', 'Yeketit', 'Megabit', 'Miyaziya', 'Ginbot', 'Sene'] as $m) {
            $cols[] = ['eth_month' => $m, 'eth_year' => $fiscalStartYear + 1];
        }
        foreach ($cols as &$c) {
            $c['label'] = $c['eth_month'] . ' ' . $c['eth_year'];
        }
        return $cols;
    }

    /**
     * Ordered P&L + bank row blueprint. Each row:
     *   ['kind' => section|blank|input|computed|bank_balance, 'label' => ...,
     *    'field' => MonthlyLedger column (input), 'calc' => formula id (computed),
     *    'account_id' => BankAccount id (bank_balance)]
     */
    public static function rows(Client $client): array
    {
        $rows = [
            ['kind' => 'section',  'label' => 'Sales Income'],
            ['kind' => 'input',    'label' => 'Cash Reg Machine', 'field' => 'cash_machine_sales'],
            ['kind' => 'input',    'label' => 'Manual',           'field' => 'manual_sales'],
            ['kind' => 'computed', 'label' => 'I) Total Sales',   'calc'  => 'total_sales'],

            ['kind' => 'section',  'label' => 'LP'],
            ['kind' => 'input',    'label' => 'A) Beginning Inventory',          'field' => 'beginning_inventory'],
            ['kind' => 'input',    'label' => 'B) This Year Purchase',           'field' => 'purchases'],
            ['kind' => 'computed', 'label' => 'C)=A+B (Available For Sales',     'calc'  => 'available'],
            ['kind' => 'input',    'label' => 'D) Ending Inventory',             'field' => 'ending_inventory'],
            ['kind' => 'computed', 'label' => 'E)=C-D( Cost of Goods Sold)',     'calc'  => 'cogs'],
            ['kind' => 'computed', 'label' => 'F) Total Sales - E',              'calc'  => 'gross'],

            ['kind' => 'blank',    'label' => ''],
            ['kind' => 'section',  'label' => 'Administration Expense'],
        ];

        foreach (self::EXPENSE_FIELDS as $field => $label) {
            $rows[] = ['kind' => 'input', 'label' => $label, 'field' => $field];
        }

        $rows[] = ['kind' => 'computed', 'label' => 'Total Expense', 'calc' => 'total_expense'];
        $rows[] = ['kind' => 'blank',    'label' => ''];
        $rows[] = ['kind' => 'computed', 'label' => 'Net Profit (F-Total Expense )', 'calc' => 'net_profit'];
        $rows[] = ['kind' => 'computed', 'label' => 'Tax (NIBT*35%-24600)',          'calc' => 'tax'];
        $rows[] = ['kind' => 'input',    'label' => 'WHT', 'field' => 'withholding_tax'];
        $rows[] = ['kind' => 'computed', 'label' => 'Profit Tax Payable',            'calc' => 'profit_tax_payable'];

        $rows[] = ['kind' => 'blank',    'label' => ''];
        $rows[] = ['kind' => 'section',  'label' => 'Bank Accounts'];

        foreach (self::bankAccounts($client) as $acct) {
            $rows[] = [
                'kind'       => 'bank_balance',
                'label'      => self::accountLabel($acct),
                'account_id' => $acct->id,
            ];
        }

        $rows[] = ['kind' => 'computed', 'label' => 'Total Bank Credited Balance', 'calc' => 'bank_sum'];

        foreach (self::BANK_MOVEMENT_FIELDS as $label => $col) {
            $rows[] = ['kind' => 'input', 'label' => $label, 'field' => 'mov:' . $col];
        }

        $rows[] = ['kind' => 'computed', 'label' => 'Total',    'calc' => 'loan_total'];
        $rows[] = ['kind' => 'computed', 'label' => 'Net Cash', 'calc' => 'net_cash'];
        $rows[] = ['kind' => 'computed', 'label' => 'Balance',  'calc' => 'balance'];

        return $rows;
    }

    /** VAT block rows (its own month header is emitted separately). */
    public static function vatRows(): array
    {
        return [
            ['kind' => 'note',  'label' => 'Sales'],          // informational, not synced
            ['kind' => 'input', 'label' => 'Sales VAT',    'field' => 'sales_vat'],
            ['kind' => 'note',  'label' => 'Purchase'],       // informational, not synced
            ['kind' => 'input', 'label' => 'Purchase VAT', 'field' => 'purchase_vat'],
        ];
    }

    /** @return \Illuminate\Support\Collection<int, BankAccount> */
    public static function bankAccounts(Client $client)
    {
        return BankAccount::withoutGlobalScopes()
            ->where('client_id', $client->id)
            ->where('is_active', true)
            ->orderBy('id')
            ->get(['id', 'bank_name', 'account_number']);
    }

    public static function accountLabel(BankAccount $acct): string
    {
        return trim(($acct->bank_name ?? 'Bank') . ' ' . ($acct->account_number ?? ''));
    }

    /** 1-based spreadsheet column index → letter (1→A, 27→AA). */
    public static function colLetter(int $index): string
    {
        $s = '';
        while ($index > 0) {
            $m = ($index - 1) % 26;
            $s = chr(65 + $m) . $s;
            $index = intdiv($index - 1, 26);
        }
        return $s;
    }
}
