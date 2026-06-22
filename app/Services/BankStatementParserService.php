<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;

/**
 * Parses a bank statement file (CSV or plain-text) and returns the
 * closing balance and any detected movement rows (loans, transfers, etc.).
 *
 * Supported formats
 * -----------------
 * 1. CBE CSV export  — columns: Date, Description, Debit, Credit, Balance
 * 2. Generic CSV     — any CSV whose last column is "Balance" / "Running Balance"
 *
 * The parser is intentionally lenient: it scans every row for a numeric
 * "Balance" column and takes the last non-null value as the closing balance.
 */
class BankStatementParserService
{
    // Keywords that indicate credit / inflow movements we care about
    private const LOAN_KEYWORDS     = ['loan', 'credit facility', 'overdraft'];
    private const TRANSFER_KEYWORDS = ['transfer', 'trf', 'wire'];
    private const LC_KEYWORDS       = ['lc margin', 'letter of credit', 'lc release'];

    /**
     * @return array{
     *   closing_balance: float|null,
     *   loan_amount: float,
     *   lc_margin_release: float,
     *   transfer_in: float,
     *   transfer_reversal: float,
     *   row_count: int,
     *   errors: string[],
     * }
     */
    public function parse(UploadedFile $file): array
    {
        $result = [
            'closing_balance'   => null,
            'loan_amount'       => 0.0,
            'lc_margin_release' => 0.0,
            'transfer_in'       => 0.0,
            'transfer_reversal' => 0.0,
            'row_count'         => 0,
            'errors'            => [],
        ];

        $content = file_get_contents($file->getRealPath());
        if ($content === false) {
            $result['errors'][] = 'Could not read the uploaded file.';
            return $result;
        }

        // Detect delimiter
        $delimiter = $this->detectDelimiter($content);

        $rows = array_map(
            fn ($line) => str_getcsv($line, $delimiter),
            array_filter(array_map('trim', explode("\n", $content)))
        );

        if (count($rows) < 2) {
            $result['errors'][] = 'File appears empty or has only one row.';
            return $result;
        }

        // Find header row (first row with at least 3 non-empty cells)
        $headerIndex = null;
        $balanceCol  = null;
        $debitCol    = null;
        $creditCol   = null;
        $descCol     = null;

        foreach ($rows as $i => $row) {
            if (count(array_filter($row)) < 3) continue;
            $lower = array_map('strtolower', array_map('trim', $row));

            // Look for a balance column
            foreach ($lower as $ci => $cell) {
                if (in_array($cell, ['balance', 'running balance', 'closing balance', 'bal'], true)) {
                    $balanceCol  = $ci;
                    $headerIndex = $i;
                }
                if (in_array($cell, ['debit', 'withdrawal', 'dr'], true))  $debitCol  = $ci;
                if (in_array($cell, ['credit', 'deposit', 'cr'], true))    $creditCol = $ci;
                if (in_array($cell, ['description', 'narration', 'particulars', 'details', 'remark'], true)) $descCol = $ci;
            }

            if ($balanceCol !== null) break;
        }

        if ($balanceCol === null) {
            // Fallback: assume last column is balance
            $balanceCol  = count($rows[0]) - 1;
            $headerIndex = 0;
            $result['errors'][] = 'Could not detect a "Balance" column — assuming last column. Please verify the result.';
        }

        // Walk data rows
        $dataRows = array_slice($rows, ($headerIndex ?? 0) + 1);
        $lastBalance = null;

        foreach ($dataRows as $row) {
            if (count($row) <= $balanceCol) continue;

            $balanceCell = $this->toFloat($row[$balanceCol] ?? '');
            if ($balanceCell !== null) {
                $lastBalance = $balanceCell;
                $result['row_count']++;
            }

            // Classify credit movements
            if ($descCol !== null && $creditCol !== null) {
                $desc   = strtolower(trim($row[$descCol] ?? ''));
                $credit = $this->toFloat($row[$creditCol] ?? '') ?? 0.0;

                if ($credit > 0) {
                    if ($this->matches($desc, self::LC_KEYWORDS)) {
                        $result['lc_margin_release'] += $credit;
                    } elseif ($this->matches($desc, self::LOAN_KEYWORDS)) {
                        $result['loan_amount'] += $credit;
                    } elseif ($this->matches($desc, self::TRANSFER_KEYWORDS)) {
                        // Negative narration = reversal
                        if (str_contains($desc, 'reversal') || str_contains($desc, 'return')) {
                            $result['transfer_reversal'] += $credit;
                        } else {
                            $result['transfer_in'] += $credit;
                        }
                    }
                }
            }
        }

        $result['closing_balance'] = $lastBalance;

        return $result;
    }

    private function detectDelimiter(string $content): string
    {
        $firstLine = strtok($content, "\n");
        $counts    = [
            ','  => substr_count($firstLine, ','),
            ';'  => substr_count($firstLine, ';'),
            "\t" => substr_count($firstLine, "\t"),
            '|'  => substr_count($firstLine, '|'),
        ];
        arsort($counts);
        return array_key_first($counts);
    }

    private function toFloat(mixed $cell): ?float
    {
        if ($cell === null || trim((string) $cell) === '') return null;
        $clean = preg_replace('/[^0-9.\-]/', '', (string) $cell);
        if ($clean === '' || $clean === '-') return null;
        return (float) $clean;
    }

    private function matches(string $text, array $keywords): bool
    {
        foreach ($keywords as $kw) {
            if (str_contains($text, $kw)) return true;
        }
        return false;
    }
}
