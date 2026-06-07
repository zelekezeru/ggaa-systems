<?php

namespace App\Services;

use App\Models\BankAccountBalance;
use App\Models\Client;
use App\Models\MonthlyLedger;
use App\Support\LedgerSheetLayout;
use RuntimeException;

/**
 * Reads the firm's real (transposed) ledger sheet — months across columns,
 * line-items down rows — and upserts the raw figures into MonthlyLedger rows.
 *
 * It walks the rows, tracking the "current" month→column map from each header
 * row it meets (so it handles both the P&L block and the year-shifted VAT
 * block). Computed rows (Total Sales, COGS, Net Profit, Tax, …) are ignored —
 * the app recomputes them. Verified periods are never overwritten.
 */
class LedgerSheetSyncService
{
    private const MONTHS = 'meskeram|tiqimt|hidar|tahisas|tirr|yeketit|megabit|miyaziya|ginbot|sene|hamle|nehase|pagume';

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

        $rows = $this->sheets->readRange($client->google_sheet_id, config('ledger_sheet.range'));
        if (count($rows) < 2) {
            return ['created' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => ['Sheet has no data rows.']];
        }

        [$fieldByLabel, $movByLabel, $bankByLabel] = $this->labelMaps($client);

        // Accumulators keyed by "ethYear_ethMonth".
        $ledgers  = [];   // [key][field] = value
        $balances = [];   // [key][account_id] = balance
        $movements = [];  // [key][balanceColumn] = value

        $colMap = [];     // colIndex => ['eth_month','eth_year'] for the current block

        foreach ($rows as $row) {
            // A header row resets the active month→column map.
            $headerCols = $this->detectHeader($row);
            if ($headerCols !== null) {
                $colMap = $headerCols;
                continue;
            }
            if (empty($colMap)) {
                continue;
            }

            $label = $this->normalize((string) ($row[0] ?? ''));
            if ($label === '') {
                continue;
            }

            if (isset($fieldByLabel[$label])) {
                $field = $fieldByLabel[$label];
                foreach ($colMap as $ci => $p) {
                    $ledgers[$this->key($p)][$field] = $this->toNumber($row[$ci] ?? null);
                }
            } elseif (isset($bankByLabel[$label])) {
                $aid = $bankByLabel[$label];
                foreach ($colMap as $ci => $p) {
                    $balances[$this->key($p)][$aid] = $this->toNumber($row[$ci] ?? null);
                }
            } elseif (isset($movByLabel[$label])) {
                $col = $movByLabel[$label];
                foreach ($colMap as $ci => $p) {
                    $movements[$this->key($p)][$col] = $this->toNumber($row[$ci] ?? null);
                }
            }
            // anything else (computed/section/note) is ignored
        }

        return $this->persist($client, $ledgers, $balances, $movements);
    }

    /**
     * Build label → target maps from the layout so reading lines up with what
     * the template wrote.
     *
     * @return array{0: array<string,string>, 1: array<string,string>, 2: array<string,int>}
     */
    private function labelMaps(Client $client): array
    {
        $fieldByLabel = [];
        $movByLabel   = [];
        $bankByLabel  = [];

        foreach (LedgerSheetLayout::rows($client) as $r) {
            if ($r['kind'] === 'input' && str_starts_with($r['field'], 'mov:')) {
                $movByLabel[$this->normalize($r['label'])] = substr($r['field'], 4);
            } elseif ($r['kind'] === 'input') {
                $fieldByLabel[$this->normalize($r['label'])] = $r['field'];
            } elseif ($r['kind'] === 'bank_balance') {
                $bankByLabel[$this->normalize($r['label'])] = $r['account_id'];
            }
        }

        // VAT block input rows
        foreach (LedgerSheetLayout::vatRows() as $vr) {
            if (($vr['kind'] ?? '') === 'input') {
                $fieldByLabel[$this->normalize($vr['label'])] = $vr['field'];
            }
        }

        return [$fieldByLabel, $movByLabel, $bankByLabel];
    }

    /**
     * If a row looks like a month header (≥3 "Month YYYY" cells), return the
     * column-index → period map; otherwise null.
     *
     * @return array<int, array{eth_month:string, eth_year:int}>|null
     */
    private function detectHeader(array $row): ?array
    {
        $found = [];
        foreach ($row as $ci => $cell) {
            if ($ci === 0) {
                continue;
            }
            if ($p = $this->parseMonthYear((string) $cell)) {
                $found[$ci] = $p;
            }
        }
        return count($found) >= 3 ? $found : null;
    }

    /** @return array{eth_month:string, eth_year:int}|null */
    private function parseMonthYear(string $cell): ?array
    {
        if (! preg_match('/^\s*(' . self::MONTHS . ')\s+(\d{4})\s*$/i', trim($cell), $m)) {
            return null;
        }
        return [
            'eth_month' => ucfirst(strtolower($m[1])),
            'eth_year'  => (int) $m[2],
        ];
    }

    /**
     * Persist the accumulated data. Verified ledgers are skipped; new periods
     * are created as draft.
     *
     * @return array{created:int, updated:int, skipped:int, errors:array<int,string>}
     */
    private function persist(Client $client, array $ledgers, array $balances, array $movements): array
    {
        $created = $updated = $skipped = 0;
        $errors  = [];

        $keys = array_unique(array_merge(
            array_keys($ledgers),
            array_keys($balances),
            array_keys($movements),
        ));

        foreach ($keys as $key) {
            [$year, $month] = explode('_', $key, 2);

            $existing = MonthlyLedger::withoutGlobalScopes()
                ->where('client_id', $client->id)
                ->where('eth_year', (int) $year)
                ->where('eth_month', $month)
                ->first();

            if ($existing && $existing->status === 'verified') {
                $skipped++;
                continue;
            }

            $attrs = $ledgers[$key] ?? [];

            // Don't create empty draft rows for months with no figures at all.
            if (! $existing && ! $this->hasAnyValue($attrs, $balances[$key] ?? [], $movements[$key] ?? [])) {
                continue;
            }

            if ($existing) {
                $existing->fill($attrs)->save();
                $ledger = $existing;
                $updated++;
            } else {
                $ledger = MonthlyLedger::withoutGlobalScopes()->create(array_merge($attrs, [
                    'client_id'    => $client->id,
                    'eth_year'     => (int) $year,
                    'eth_month'    => $month,
                    'status'       => 'draft',
                    'submitted_by' => auth()->id(),
                ]));
                $created++;
            }

            $this->syncBankRows($ledger, $balances[$key] ?? [], $movements[$key] ?? []);
        }

        $client->forceFill(['sheet_synced_at' => now()])->save();

        return compact('created', 'updated', 'skipped', 'errors');
    }

    /**
     * Write per-account balances; the firm-level movement rows (loan/LC/
     * transfer) are attached to the first account, matching how the ledger UI
     * aggregates them.
     *
     * @param  array<int, float>     $balances   account_id => balance
     * @param  array<string, float>  $movements  column => value
     */
    private function syncBankRows(MonthlyLedger $ledger, array $balances, array $movements): void
    {
        $firstAccount = array_key_first($balances);

        foreach ($balances as $accountId => $balance) {
            BankAccountBalance::updateOrCreate(
                ['bank_account_id' => $accountId, 'monthly_ledger_id' => $ledger->id],
                array_merge(['balance' => $balance], $accountId === $firstAccount ? $movements : [])
            );
        }
    }

    private function key(array $period): string
    {
        return $period['eth_year'] . '_' . $period['eth_month'];
    }

    /** True if any P&L field, bank balance, or movement value is non-zero. */
    private function hasAnyValue(array $attrs, array $balances, array $movements): bool
    {
        foreach (array_merge(array_values($attrs), array_values($balances), array_values($movements)) as $v) {
            if ((float) $v !== 0.0) {
                return true;
            }
        }
        return false;
    }

    private function normalize(string $value): string
    {
        return preg_replace('/\s+/', ' ', strtolower(trim($value)));
    }

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
