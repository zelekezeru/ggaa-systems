<?php

namespace App\Exports;

use App\Models\Client;
use App\Support\LedgerSheetLayout;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;

/**
 * Generates the client ledger template in the firm's real format:
 * months across the columns (Hamle→Sene fiscal year), line-items down the
 * rows, with computed rows (Total Sales, COGS, Gross/Net Profit, Tax, bank
 * roll-forward) written as live formulas, plus the client's registered bank
 * accounts and a Monthly VAT Report block.
 *
 * Worksheet title is "Ledger" so importing/applying yields the tab the sync
 * reads.
 */
class LedgerSheetTemplateExport implements FromArray, WithTitle, WithEvents
{
    public function __construct(
        protected Client $client,
        protected int $fiscalStartYear,
    ) {}

    public function title(): string
    {
        return 'Ledger';
    }

    public function array(): array
    {
        return self::grid($this->client, $this->fiscalStartYear);
    }

    /**
     * Build the full 2-D grid (header + rows + VAT block). Shared by the XLSX
     * download and the Google Sheets writer.
     *
     * @return array<int, array<int, string|int|float>>
     */
    public static function grid(Client $client, int $fiscalStartYear): array
    {
        $months    = LedgerSheetLayout::monthColumns($fiscalStartYear);
        $vatMonths = LedgerSheetLayout::monthColumns($fiscalStartYear - 1);
        $pnl       = LedgerSheetLayout::rows($client);

        $n      = count($months);
        $firstC = 2;                 // column B (A holds row labels)
        $lastC  = $firstC + $n - 1;
        $totalC = $lastC + 1;
        $firstL = LedgerSheetLayout::colLetter($firstC);
        $lastL  = LedgerSheetLayout::colLetter($lastC);

        // ── Pass 1: assign spreadsheet row numbers + collect formula refs ──
        $rowNo = 1; // header occupies row 1
        $ref = [];          // 'field:x' | 'calc:x' => row number
        $expRows = [];      // expense input rows
        $bankRows = [];     // bank balance rows
        $movRows = [];      // bank movement rows
        foreach ($pnl as $i => $r) {
            $rn = ++$rowNo;
            $pnl[$i]['_rn'] = $rn;
            $kind = $r['kind'];
            if ($kind === 'input') {
                $ref['field:' . $r['field']] = $rn;
                if (isset(LedgerSheetLayout::EXPENSE_FIELDS[$r['field']])) $expRows[] = $rn;
                if (str_starts_with($r['field'], 'mov:')) $movRows[] = $rn;
            } elseif ($kind === 'computed') {
                $ref['calc:' . $r['calc']] = $rn;
            } elseif ($kind === 'bank_balance') {
                $bankRows[] = $rn;
            }
        }

        $sumRow = fn (int $rn) => "=SUM({$firstL}{$rn}:{$lastL}{$rn})";

        // ── Header ──
        $grid = [];
        $grid[] = array_merge(['Ref'], array_column($months, 'label'), ['Total']);

        // ── P&L + bank rows ──
        foreach ($pnl as $r) {
            $line = array_fill(0, $totalC, '');
            $line[0] = $r['label'];
            $rn = $r['_rn'];

            if ($r['kind'] === 'section' || $r['kind'] === 'blank') {
                $grid[] = $line;
                continue;
            }

            if ($r['kind'] === 'input' || $r['kind'] === 'bank_balance') {
                $line[$totalC - 1] = $sumRow($rn);   // months blank for entry
                $grid[] = $line;
                continue;
            }

            // computed → per-month formula + row total
            for ($c = $firstC; $c <= $lastC; $c++) {
                $line[$c - 1] = self::formula($r['calc'], LedgerSheetLayout::colLetter($c), $ref, $expRows, $bankRows, $movRows);
            }
            $line[$totalC - 1] = $r['calc'] === 'balance' ? '' : $sumRow($rn);
            $grid[] = $line;
        }

        // ── Monthly VAT Report block (its own month header, one year back) ──
        $grid[] = array_fill(0, $totalC, '');
        $vatTitle = array_fill(0, $totalC, '');
        $vatTitle[0] = 'Monthly VAT REPORT';
        $grid[] = $vatTitle;
        $grid[] = array_merge([''], array_column($vatMonths, 'label'), ['Total']);

        foreach (LedgerSheetLayout::vatRows() as $vr) {
            $line = array_fill(0, $totalC, '');
            $line[0] = $vr['label'];
            $rn = count($grid) + 1; // 1-based row of the line being pushed
            $line[$totalC - 1] = $sumRow($rn);
            $grid[] = $line;
        }

        return $grid;
    }

    /** Build a computed-cell formula for column letter $cl. */
    private static function formula(string $calc, string $cl, array $ref, array $expRows, array $bankRows, array $movRows): string
    {
        $f = fn (string $key) => $cl . ($ref[$key] ?? 1);

        return match ($calc) {
            'total_sales'        => "={$f('field:cash_machine_sales')}+{$f('field:manual_sales')}",
            'available'          => "={$f('field:beginning_inventory')}+{$f('field:purchases')}",
            'cogs'               => "={$f('calc:available')}-{$f('field:ending_inventory')}",
            'gross'              => "={$f('calc:total_sales')}-{$f('calc:cogs')}",
            'total_expense'      => $expRows ? "=SUM({$cl}" . min($expRows) . ":{$cl}" . max($expRows) . ")" : '0',
            'net_profit'         => "={$f('calc:gross')}-{$f('calc:total_expense')}",
            'tax'                => "=MAX(0,{$f('calc:net_profit')}*0.35-24600)",
            'profit_tax_payable' => "={$f('calc:tax')}-{$f('field:withholding_tax')}",
            'bank_sum'           => $bankRows ? "=SUM({$cl}" . min($bankRows) . ":{$cl}" . max($bankRows) . ")" : '0',
            'loan_total'         => $movRows ? "=SUM({$cl}" . min($movRows) . ":{$cl}" . max($movRows) . ")" : '0',
            'net_cash'           => "={$f('calc:bank_sum')}+{$f('calc:loan_total')}",
            default              => '',
        };
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet   = $event->sheet->getDelegate();
                $lastCol = $sheet->getHighestColumn();

                $sheet->getStyle("A1:{$lastCol}1")->getFont()->setBold(true);
                $sheet->getStyle("A1:{$lastCol}1")->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('FCE8B2');
                $sheet->freezePane('B2');
                $sheet->getColumnDimension('A')->setWidth(32);
            },
        ];
    }
}
