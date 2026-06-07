<?php

namespace App\Services;

use App\Models\Client;
use App\Models\MonthlyLedger;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Pulls raw monthly figures from a client's Google Sheet and upserts them into
 * MonthlyLedger rows. The sheet is the entry surface; PHP accessors still
 * compute totals/profit/tax downstream, so exports, invoicing and the client
 * portal keep working unchanged.
 *
 * Safety rules:
 *  - Rows already `verified` are never overwritten (protects signed-off data).
 *  - Only `writable_fields` from config are touched; workflow columns are safe.
 *  - Incoming entries land as `draft`; verification stays a manual action.
 */
class LedgerSheetSyncService
{
    public function __construct(private GoogleSheetsClient $sheets)
    {
    }

    /**
     * @return array{created:int, updated:int, skipped:int, errors:array<int,string>}
     */
    public function sync(Client $client): array
    {
        if (empty($client->google_sheet_id)) {
            throw new RuntimeException('This client has no Google Sheet linked yet.');
        }

        $cfg    = config('ledger_sheet');
        $rows   = $this->sheets->readRange($client->google_sheet_id, $cfg['range']);
        $months = array_map('strtolower', MonthlyLedger::ethiopianMonths());

        if (count($rows) < 2) {
            return ['created' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => ['Sheet has no data rows.']];
        }

        // First row = headers → resolve each column to a field key (or custom label).
        $headerRow = array_shift($rows);
        $columns   = $this->resolveColumns($headerRow, $cfg);

        $created = $updated = $skipped = 0;
        $errors  = [];

        foreach ($rows as $i => $row) {
            $lineNo = $i + 2; // human-friendly sheet row number (1-based + header)

            [$attributes, $customExpenses] = $this->mapRow($row, $columns, $cfg);

            // Need a valid period to key the record.
            $month = isset($attributes['eth_month']) ? trim((string) $attributes['eth_month']) : '';
            $year  = isset($attributes['eth_year']) ? (int) $attributes['eth_year'] : 0;

            if ($month === '' && $year === 0) {
                continue; // blank spacer row — silently ignore
            }

            $monthIdx = array_search(strtolower($month), $months, true);
            if ($monthIdx === false) {
                $errors[] = "Row {$lineNo}: unknown Ethiopian month \"{$month}\".";
                $skipped++;
                continue;
            }
            if ($year < 2000 || $year > 2100) {
                $errors[] = "Row {$lineNo}: invalid Ethiopian year \"{$year}\".";
                $skipped++;
                continue;
            }

            $attributes['eth_month'] = MonthlyLedger::ethiopianMonths()[$monthIdx]; // canonical casing
            $attributes['eth_year']  = $year;

            if (! empty($customExpenses)) {
                $attributes['custom_expenses'] = $customExpenses;
            }

            $existing = MonthlyLedger::withoutGlobalScopes()
                ->where('client_id', $client->id)
                ->where('eth_year', $year)
                ->where('eth_month', $attributes['eth_month'])
                ->first();

            if ($existing && $existing->status === 'verified') {
                $skipped++; // never overwrite verified data
                continue;
            }

            if ($existing) {
                $existing->fill($attributes)->save();
                $updated++;
            } else {
                MonthlyLedger::withoutGlobalScopes()->create(array_merge($attributes, [
                    'client_id'    => $client->id,
                    'status'       => 'draft',
                    'submitted_by' => auth()->id(),
                ]));
                $created++;
            }
        }

        $client->forceFill(['sheet_synced_at' => now()])->save();

        return compact('created', 'updated', 'skipped', 'errors');
    }

    /**
     * Map header cells to field keys. Returns an index → descriptor array where
     * each descriptor is ['field' => key] or ['custom' => label].
     *
     * @param  array<int,string>  $headerRow
     * @return array<int, array{field?:string, custom?:string}>
     */
    private function resolveColumns(array $headerRow, array $cfg): array
    {
        $aliases  = $cfg['aliases'];
        $writable = array_flip($cfg['writable_fields']);
        $prefix   = strtolower($cfg['custom_expense_prefix']);
        $columns  = [];

        foreach ($headerRow as $idx => $raw) {
            $label = trim((string) $raw);
            if ($label === '') {
                continue;
            }

            // Custom expense column: "Custom: Insurance"
            if (str_starts_with(strtolower($label), $prefix)) {
                $customLabel = trim(substr($label, strlen($prefix)));
                if ($customLabel !== '') {
                    $columns[$idx] = ['custom' => $customLabel];
                }
                continue;
            }

            $norm  = $this->normalize($label);
            $field = $aliases[$norm] ?? $norm; // alias → field, else assume header IS the field key

            if (isset($writable[$field]) || in_array($field, ['eth_year', 'eth_month'], true)) {
                $columns[$idx] = ['field' => $field];
            }
            // Unknown / non-writable headers are ignored.
        }

        return $columns;
    }

    /**
     * Build the attribute array + custom-expense list for one data row.
     *
     * @param  array<int,string>  $row
     * @param  array<int, array{field?:string, custom?:string}>  $columns
     * @return array{0: array<string,mixed>, 1: array<int, array{label:string, amount:float}>}
     */
    private function mapRow(array $row, array $columns, array $cfg): array
    {
        $numeric = array_flip($cfg['numeric_fields']);
        $attrs   = [];
        $custom  = [];

        foreach ($columns as $idx => $desc) {
            $cell = $row[$idx] ?? null;

            if (isset($desc['custom'])) {
                $amount = $this->toNumber($cell);
                if ($amount != 0.0) {
                    $custom[] = ['label' => $desc['custom'], 'amount' => $amount];
                }
                continue;
            }

            $field = $desc['field'];
            if (isset($numeric[$field])) {
                $attrs[$field] = $this->toNumber($cell);
            } else {
                $attrs[$field] = $cell === null ? null : trim((string) $cell);
            }
        }

        return [$attrs, $custom];
    }

    /** Normalise a header: lowercase, collapse non-alphanumerics to "_". */
    private function normalize(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9]+/', '_', $value);
        return trim($value, '_');
    }

    /** Coerce a sheet cell to a float, tolerating "1,234.50", "ETB 10", blanks. */
    private function toNumber(mixed $cell): float
    {
        if ($cell === null || $cell === '') {
            return 0.0;
        }
        if (is_numeric($cell)) {
            return (float) $cell;
        }
        $clean = preg_replace('/[^0-9.\-]/', '', (string) $cell);
        return $clean === '' || $clean === '-' ? 0.0 : (float) $clean;
    }
}
