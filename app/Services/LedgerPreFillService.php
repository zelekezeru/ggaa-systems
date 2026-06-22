<?php

namespace App\Services;

use App\Models\Client;
use App\Models\MonthlyLedger;
use App\Models\PurchaseReceipt;

/**
 * Computes smart pre-fill suggestions for a new ledger entry.
 *
 * Rules:
 *  - beginning_inventory  → previous month's ending_inventory (exact carry-forward)
 *  - cash_machine_start_number → previous month's end_number + 1
 *  - manual_receipt_start_number → previous month's end_number + 1
 *  - Fixed expenses → field value if it has been identical for the last 3+ completed months
 *  - purchases → sum of raw_material PurchaseReceipts captured for this period
 */
class LedgerPreFillService
{
    // Standard expense fields eligible for pattern detection
    private const EXPENSE_FIELDS = [
        'salary_expense',
        'pension_expense',
        'printing_expense',
        'shed_rent',
        'stationery_expense',
        'office_rent_expense',
        'transport_expense',
        'machine_fa_expense',
        'eeu_expense',
        'maintenance_expense',
        'advertising_expense',
        'uniform_expense',
        'indirect_materials_expense',
        'depreciation_expense',
        'legal_fee_expense',
        'bank_interest_expense',
        'bank_service_charge',
    ];

    /**
     * Build the pre-fill suggestion payload for a given client / period.
     *
     * @return array{
     *   prefills: array<string, float|int|null>,
     *   fixed_fields: string[],
     *   receipts_total: float,
     *   receipts_count: int,
     *   source: array<string, string>,
     * }
     */
    public function suggest(Client $client, string $ethMonth, int $ethYear): array
    {
        $prefills     = [];
        $fixedFields  = [];   // fields flagged as "detected fixed — pre-filled"
        $source       = [];   // human-readable source per field

        // ── 1. Previous month figures ──────────────────────────────────────────
        $prev = $this->previousLedger($client, $ethMonth, $ethYear);

        if ($prev) {
            // Carry forward ending inventory → beginning inventory
            if ($prev->ending_inventory !== null && (float) $prev->ending_inventory > 0) {
                $prefills['beginning_inventory'] = (float) $prev->ending_inventory;
                $source['beginning_inventory']   = "Carried from {$prev->eth_month} {$prev->eth_year} ending inventory";
            }

            // Receipt number ranges
            if ($prev->cash_machine_end_number !== null) {
                $prefills['cash_machine_start_number'] = (int) $prev->cash_machine_end_number + 1;
                $source['cash_machine_start_number']   = "Continues from {$prev->eth_month} {$prev->eth_year} (end #{$prev->cash_machine_end_number})";
            }

            if ($prev->manual_receipt_end_number !== null) {
                $prefills['manual_receipt_start_number'] = (int) $prev->manual_receipt_end_number + 1;
                $source['manual_receipt_start_number']   = "Continues from {$prev->eth_month} {$prev->eth_year} (end #{$prev->manual_receipt_end_number})";
            }

            // Inventory unit tracking
            if ($prev->inventory_items_end !== null) {
                $prefills['inventory_items_start'] = (float) $prev->inventory_items_end;
                $source['inventory_items_start']   = "Carried from {$prev->eth_month} {$prev->eth_year} ending units";
            }
        }

        // ── 2. Fixed expense pattern detection ────────────────────────────────
        // Look at the last 3 completed (submitted/verified) ledgers for this client.
        $history = MonthlyLedger::withoutGlobalScopes()
            ->where('client_id', $client->id)
            ->whereIn('status', ['submitted', 'verified'])
            ->orderByDesc('eth_year')
            ->orderByDesc(fn ($q) => $q->selectRaw(
                'FIELD(eth_month, ' . implode(',', array_map(
                    fn ($m) => "'$m'",
                    array_reverse(MonthlyLedger::ethiopianMonths())
                )) . ')'
            ))
            ->limit(3)
            ->get(array_merge(['id'], self::EXPENSE_FIELDS));

        if ($history->count() >= 2) {
            foreach (self::EXPENSE_FIELDS as $field) {
                $values = $history->pluck($field)->map(fn ($v) => round((float) $v, 2))->unique();

                // All historical values are the same non-zero amount → fixed
                if ($values->count() === 1 && $values->first() > 0) {
                    $amount = $values->first();
                    $prefills[$field]   = $amount;
                    $fixedFields[]      = $field;
                    $source[$field]     = "Detected fixed ({$history->count()} months consistent): {$amount}";
                }
            }
        }

        // ── 3. Purchases from captured receipts ───────────────────────────────
        $receiptsTotal = (float) PurchaseReceipt::withoutGlobalScopes()
            ->where('client_id', $client->id)
            ->where('eth_month', $ethMonth)
            ->where('eth_year', $ethYear)
            ->whereIn('expense_category', ['raw_material', 'detergent'])
            ->sum('amount_before_vat');

        $receiptsCount = (int) PurchaseReceipt::withoutGlobalScopes()
            ->where('client_id', $client->id)
            ->where('eth_month', $ethMonth)
            ->where('eth_year', $ethYear)
            ->count();

        if ($receiptsTotal > 0) {
            $prefills['purchases'] = $receiptsTotal;
            $source['purchases']   = "Summed from {$receiptsCount} captured receipts";
        }

        // ── 4. VAT from receipts ──────────────────────────────────────────────
        $salesVatFromReceipts = (float) PurchaseReceipt::withoutGlobalScopes()
            ->where('client_id', $client->id)
            ->where('eth_month', $ethMonth)
            ->where('eth_year', $ethYear)
            ->sum('vat_amount');

        if ($salesVatFromReceipts > 0) {
            $prefills['purchase_vat'] = $salesVatFromReceipts;
            $source['purchase_vat']   = "Summed VAT from {$receiptsCount} captured receipts";
        }

        return [
            'prefills'       => $prefills,
            'fixed_fields'   => $fixedFields,
            'receipts_total' => $receiptsTotal,
            'receipts_count' => $receiptsCount,
            'source'         => $source,
        ];
    }

    /**
     * Find the most recent ledger entry before the given period.
     */
    private function previousLedger(Client $client, string $ethMonth, int $ethYear): ?MonthlyLedger
    {
        $months       = MonthlyLedger::ethiopianMonths();
        $currentIndex = array_search($ethMonth, $months);

        if ($currentIndex === false) return null;

        if ($currentIndex > 0) {
            $prevMonth = $months[$currentIndex - 1];
            $prevYear  = $ethYear;
        } else {
            // Wrap to last month of previous year
            $prevMonth = $months[count($months) - 1];
            $prevYear  = $ethYear - 1;
        }

        return MonthlyLedger::withoutGlobalScopes()
            ->where('client_id', $client->id)
            ->where('eth_month', $prevMonth)
            ->where('eth_year', $prevYear)
            ->first();
    }
}
