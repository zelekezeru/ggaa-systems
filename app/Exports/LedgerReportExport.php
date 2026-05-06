<?php

namespace App\Exports;

use App\Models\MonthlyLedger;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;

class LedgerReportExport implements FromArray, WithTitle, WithEvents
{
    public function __construct(protected MonthlyLedger $ledger) {}

    public function title(): string
    {
        return $this->ledger->eth_month . ' ' . $this->ledger->eth_year;
    }

    public function array(): array
    {
        $l = $this->ledger->loadMissing(['client', 'bankAccountBalances.bankAccount', 'submittedBy', 'verifiedBy']);

        $rows = [
            [$l->client->company_name . ' — Monthly Financial Ledger'],
            ['TIN: ' . ($l->client->tin_number ?? '—'), '', 'Period: ' . $l->eth_month . ' ' . $l->eth_year],
            ['Status: ' . strtoupper($l->status)],
            [],
            ['I. SALES INCOME'],
            ['Cash Register Machine Sales',  (float) $l->cash_machine_sales],
            ['Manual (Hand-to-Hand) Sales',  (float) $l->manual_sales],
            ['Total Sales',                   $l->total_sales],
            [],
            ['II. COST OF GOODS SOLD'],
            ['A) Beginning Inventory',       (float) $l->beginning_inventory],
            ['B) Purchases',                 (float) $l->purchases],
            ['C = A+B) Available for Sales', $l->available_for_sales],
            ['D) Ending Inventory',          (float) $l->ending_inventory],
            ['E = C-D) Cost of Goods Sold',  $l->cost_of_goods_sold],
            ['F = Sales-COGS) Gross Profit', $l->gross_profit],
            [],
            ['III. ADMINISTRATION EXPENSES'],
            ['Salary',              (float) $l->salary_expense],
            ['Pension 11%',         (float) $l->pension_expense],
            ['EEU (Electricity)',   (float) $l->eeu_expense],
            ['Maintenance',         (float) $l->maintenance_expense],
            ['Machine / Fixed Asset', (float) $l->machine_fa_expense],
            ['Office Rent',         (float) $l->office_rent_expense],
            ['Shed Rent',           (float) $l->shed_rent],
            ['Transportation',      (float) $l->transport_expense],
            ['Printing',            (float) $l->printing_expense],
            ['Stationery',          (float) $l->stationery_expense],
            ['Advertising',         (float) $l->advertising_expense],
            ['Employee Uniform',    (float) $l->uniform_expense],
            ['Indirect Materials',  (float) $l->indirect_materials_expense],
            ['Depreciation',        (float) $l->depreciation_expense],
            ['Legal Fee',           (float) $l->legal_fee_expense],
            ['Bank Interest',       (float) $l->bank_interest_expense],
            ['Bank Service Charge', (float) $l->bank_service_charge],
            ['Total Expenses',      $l->total_expenses],
            [],
            ['IV. NET RESULT'],
            ['Net Profit',          $l->net_profit],
            ['Profit Tax (35% − 24,600)', $l->profit_tax],
            [],
            ['V. VAT REPORT'],
            ['Sales VAT (15%)',     (float) $l->sales_vat],
            ['Purchase VAT',        (float) $l->purchase_vat],
            ['Withholding Tax',     (float) $l->withholding_tax],
        ];

        if ($l->bankAccountBalances->count() > 0) {
            $rows[] = [];
            $rows[] = ['VI. BANK ACCOUNT BALANCES'];
            $rows[] = ['Bank', 'Account #', 'Balance', 'Loan', 'LC Margin Release', 'Transfer In', 'Transfer Reversal'];
            foreach ($l->bankAccountBalances as $b) {
                $rows[] = [
                    $b->bankAccount->bank_name ?? '—',
                    $b->bankAccount->account_number ?? '—',
                    (float) $b->balance,
                    (float) $b->loan_amount,
                    (float) $b->lc_margin_release,
                    (float) $b->transfer_in,
                    (float) $b->transfer_reversal,
                ];
            }
            $rows[] = ['Total Bank Balance', '', $l->total_bank_balance];
        }

        if ($l->notes) {
            $rows[] = [];
            $rows[] = ['NOTES'];
            $rows[] = [$l->notes];
        }

        $rows[] = [];
        $rows[] = ['Submitted by: ' . ($l->submittedBy?->name ?? '—')];
        $rows[] = ['Verified by: ' . ($l->verifiedBy?->name ?? '—')];

        return $rows;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $sheet->getColumnDimension('A')->setWidth(38);
                $sheet->getColumnDimension('B')->setWidth(20);
                $sheet->getColumnDimension('C')->setWidth(20);
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
            },
        ];
    }
}
