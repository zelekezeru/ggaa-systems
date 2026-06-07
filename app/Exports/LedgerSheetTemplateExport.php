<?php

namespace App\Exports;

use App\Models\Client;
use App\Models\MonthlyLedger;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;

/**
 * Generates the system-compatible Google Sheets entry template for a client.
 *
 * The header row uses labels that the sync mapping (config/ledger_sheet.php)
 * resolves to MonthlyLedger fields, so a sheet built from this template is
 * guaranteed readable by "Sync from Sheet". One pre-filled row per Ethiopian
 * month; staff only fill the numeric cells.
 *
 * The worksheet is titled "Ledger" so that importing the file into Google
 * Sheets yields a tab matching GOOGLE_SHEETS_TAB.
 */
class LedgerSheetTemplateExport implements FromArray, WithTitle, WithEvents
{
    /**
     * Ordered header labels. Each must normalise to a field in the sync config.
     * Keep in sync with config/ledger_sheet.php aliases.
     */
    public const HEADERS = [
        'Month', 'Year',
        'Cash Machine Sales', 'Cash Start', 'Cash End',
        'Manual Sales', 'Manual Start', 'Manual End',
        'Beginning Inventory', 'Purchases', 'Ending Inventory',
        'Units Start', 'Units End', 'Units Sold',
        'Salary', 'Pension', 'Printing', 'Shed Rent', 'Stationery',
        'Office Rent', 'Transport', 'Machine FA', 'EEU', 'Maintenance',
        'Advertising', 'Uniform', 'Indirect Materials', 'Depreciation',
        'Legal Fee', 'Bank Interest', 'Bank Service Charge',
        'Sales VAT', 'Purchase VAT', 'Withholding Tax',
        'Tax Rate', 'Notes', 'Custom: Example',
    ];

    public function __construct(
        protected Client $client,
        protected int $ethYear,
        protected float $defaultTaxRate = 35.0,
    ) {}

    public function title(): string
    {
        return 'Ledger';
    }

    public function array(): array
    {
        return self::templateRows($this->ethYear, $this->defaultTaxRate);
    }

    /**
     * Single source of truth for the template grid (header row + one row per
     * Ethiopian month). Reused both by the XLSX download and by the Google
     * Sheets writer that injects the template into a linked workbook.
     *
     * @return array<int, array<int, string|int|float>>
     */
    public static function templateRows(int $ethYear, float $defaultTaxRate = 35.0): array
    {
        $rows  = [self::HEADERS];
        $width = count(self::HEADERS);
        $taxIx = array_search('Tax Rate', self::HEADERS, true);

        foreach (MonthlyLedger::ethiopianMonths() as $month) {
            $row         = array_fill(0, $width, '');
            $row[0]      = $month;           // Month
            $row[1]      = $ethYear;         // Year
            $row[$taxIx] = $defaultTaxRate;  // sensible default
            $rows[]      = $row;
        }

        return $rows;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet    = $event->sheet->getDelegate();
                $lastCol  = $sheet->getHighestColumn();
                $lastRow  = $sheet->getHighestRow();

                // Bold + colour the header row, then freeze it.
                $sheet->getStyle("A1:{$lastCol}1")->getFont()->setBold(true);
                $sheet->getStyle("A1:{$lastCol}1")->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('D1FAE5');
                $sheet->freezePane('A2');

                // Auto-size every column for readability.
                foreach (range('A', $lastCol) as $col) {
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                }

                // Protect the Month + Year columns visually (they're pre-filled);
                // a light grey fill signals "don't edit these".
                $sheet->getStyle("A2:B{$lastRow}")->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('F3F4F6');
            },
        ];
    }
}
