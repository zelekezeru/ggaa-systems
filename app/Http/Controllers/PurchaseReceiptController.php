<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\MonthlyLedger;
use App\Models\PurchaseReceipt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class PurchaseReceiptController extends Controller
{
    /**
     * List receipts for a client + period (returned as JSON for the Vue component).
     */
    public function index(Request $request, Client $client)
    {
        abort_unless($client->exists, 403);

        $validated = $request->validate([
            'eth_month' => 'required|string',
            'eth_year'  => 'required|integer',
        ]);

        $receipts = PurchaseReceipt::where('client_id', $client->id)
            ->where('eth_month', $validated['eth_month'])
            ->where('eth_year', $validated['eth_year'])
            ->with('capturedBy:id,name')
            ->orderBy('receipt_date')
            ->orderBy('id')
            ->get()
            ->map(fn ($r) => [
                'id'               => $r->id,
                'supplier_name'    => $r->supplier_name,
                'receipt_date'     => $r->receipt_date?->format('Y-m-d'),
                'invoice_number'   => $r->invoice_number,
                'description'      => $r->description,
                'expense_category' => $r->expense_category,
                'amount_before_vat'=> (float) $r->amount_before_vat,
                'vat_amount'       => (float) $r->vat_amount,
                'total_amount'     => $r->total_amount,
                'has_vat_receipt'  => $r->has_vat_receipt,
                'image_url'        => $r->image_url,
                'captured_by'      => $r->capturedBy?->name,
            ]);

        // Running totals by expense category
        $totals = $receipts->groupBy('expense_category')
            ->map(fn ($group) => round($group->sum('amount_before_vat'), 2));

        return response()->json([
            'receipts'        => $receipts,
            'totals'          => $totals,
            'grand_total'     => round($receipts->sum('amount_before_vat'), 2),
            'total_vat'       => round($receipts->sum('vat_amount'), 2),
            'category_labels' => PurchaseReceipt::categoryLabels(),
        ]);
    }

    /**
     * Store a new receipt (with optional image upload).
     */
    public function store(Request $request, Client $client)
    {
        abort_unless($client->exists, 403);

        $validated = $request->validate([
            'eth_month'        => 'required|string|in:' . implode(',', MonthlyLedger::ethiopianMonths()),
            'eth_year'         => 'required|integer|min:2000|max:2100',
            'supplier_name'    => 'nullable|string|max:150',
            'receipt_date'     => 'nullable|date',
            'invoice_number'   => 'nullable|string|max:60',
            'description'      => 'nullable|string|max:255',
            'expense_category' => 'required|in:' . implode(',', array_keys(PurchaseReceipt::categoryLabels())),
            'amount_before_vat'=> 'required|numeric|min:0',
            'vat_amount'       => 'nullable|numeric|min:0',
            'has_vat_receipt'  => 'boolean',
            'image'            => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120', // 5 MB
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store(
                'receipts/' . $client->id,
                'public'
            );
        }

        PurchaseReceipt::create([
            'client_id'         => $client->id,
            'eth_month'         => $validated['eth_month'],
            'eth_year'          => $validated['eth_year'],
            'supplier_name'     => $validated['supplier_name'] ?? null,
            'receipt_date'      => $validated['receipt_date'] ?? null,
            'invoice_number'    => $validated['invoice_number'] ?? null,
            'description'       => $validated['description'] ?? null,
            'expense_category'  => $validated['expense_category'],
            'amount_before_vat' => $validated['amount_before_vat'],
            'vat_amount'        => $validated['vat_amount'] ?? 0,
            'has_vat_receipt'   => $validated['has_vat_receipt'] ?? false,
            'image_path'        => $imagePath,
            'captured_by'       => Auth::id(),
        ]);

        return back()->with('success', 'Receipt captured.');
    }

    /**
     * Delete a receipt (and its stored image).
     */
    public function destroy(PurchaseReceipt $receipt)
    {
        // Ownership enforced by global scope + policy check
        abort_unless(
            Auth::user()->hasAnyRole(['Super Admin', 'Branch Manager', 'Operation Manager'])
            || $receipt->captured_by === Auth::id(),
            403
        );

        if ($receipt->image_path) {
            Storage::disk('public')->delete($receipt->image_path);
        }

        $receipt->delete();

        return back()->with('success', 'Receipt deleted.');
    }

    /**
     * Bulk-apply captured receipts to a ledger entry's expense totals.
     * This groups receipt amounts by their mapped ledger field and returns
     * the suggested field values — the controller does NOT auto-save the
     * ledger; the user confirms via the form.
     */
    public function summarize(Request $request, Client $client)
    {
        abort_unless($client->exists, 403);

        $validated = $request->validate([
            'eth_month' => 'required|string',
            'eth_year'  => 'required|integer',
        ]);

        $receipts = PurchaseReceipt::withoutGlobalScopes()
            ->where('client_id', $client->id)
            ->where('eth_month', $validated['eth_month'])
            ->where('eth_year', $validated['eth_year'])
            ->get(['expense_category', 'amount_before_vat', 'vat_amount']);

        $fieldTotals = [];
        $totalVat    = 0.0;

        foreach ($receipts as $r) {
            $field = PurchaseReceipt::categoryToLedgerField($r->expense_category);
            if ($field) {
                $fieldTotals[$field] = ($fieldTotals[$field] ?? 0) + (float) $r->amount_before_vat;
            }
            $totalVat += (float) $r->vat_amount;
        }

        if ($totalVat > 0) {
            $fieldTotals['purchase_vat'] = $totalVat;
        }

        return response()->json(['field_totals' => $fieldTotals]);
    }
}
