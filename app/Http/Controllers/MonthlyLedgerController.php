<?php

namespace App\Http\Controllers;

use App\Exports\LedgerReportExport;
use App\Exports\LedgerSheetTemplateExport;
use App\Models\BankAccount;
use App\Models\BankAccountBalance;
use App\Models\Client;
use App\Models\MonthlyLedger;
use App\Services\GoogleSheetsClient;
use App\Services\LedgerSheetSyncService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class MonthlyLedgerController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $clients = Client::with(['assignedEmployee', 'branch', 'user'])
            ->get()
            ->map(function ($client) {
                $ledgers = MonthlyLedger::where('client_id', $client->id)
                    ->get()
                    ->keyBy(fn ($l) => $l->eth_year . '_' . $l->eth_month)
                    ->map(fn ($l) => $l->only(['id', 'eth_year', 'eth_month', 'status']) + ['net_profit' => $l->net_profit]);

                return [
                    'id'                   => $client->id,
                    'company_name'         => $client->company_name,
                    'tin_number'           => $client->tin_number,
                    'email'                => $client->user?->email,
                    'email_verified'       => !!$client->user?->email_verified_at,
                    'branch'               => $client->branch?->name,
                    'assigned_employee'    => $client->assignedEmployee?->name,
                    'ledger_summary'       => $ledgers,
                ];
            });

        return Inertia::render('Ledger/Index', [
            'clients'         => $clients,
            'ethiopianMonths' => MonthlyLedger::ethiopianMonths(),
            'currentEthYear'  => $this->currentEthYear(),
            'canVerify'       => $user->hasAnyRole(['Super Admin', 'Branch Manager']),
        ]);
    }

    public function show(Client $client)
    {
        // The Client global scope enforces branch/employee access automatically.
        // If the user can't see this client, the model will 404.
        abort_unless(Client::find($client->id), 403);

        $user = Auth::user();

        $ledgers = MonthlyLedger::where('client_id', $client->id)
            ->with(['submittedBy:id,name', 'verifiedBy:id,name', 'bankAccountBalances.bankAccount'])
            ->get()
            ->keyBy(fn ($l) => $l->eth_year . '_' . $l->eth_month);

        $bankAccounts = BankAccount::where('client_id', $client->id)
            ->where('is_active', true)
            ->get(['id', 'bank_name', 'account_number', 'account_type']);

        return Inertia::render('Ledger/Entry', [
            'client'          => [
                'id'           => $client->id,
                'company_name' => $client->company_name,
                'tin_number'   => $client->tin_number,
                'email'        => $client->user?->email,
                'email_verified' => !!$client->user?->email_verified_at,
                'google_sheet_id' => $client->google_sheet_id,
                'sheet_synced_at' => $client->sheet_synced_at,
            ],
            'canLinkSheet'    => $user->hasAnyRole(['Super Admin', 'Operation Manager', 'Branch Manager']),
            'ledgers'         => $ledgers,
            'bankAccounts'    => $bankAccounts,
            'ethiopianMonths' => MonthlyLedger::ethiopianMonths(),
            'currentEthYear'  => $this->currentEthYear(),
            'canVerify'       => $user->hasAnyRole(['Super Admin', 'Branch Manager']),
        ]);
    }

    public function store(Request $request, Client $client)
    {
        abort_unless($client->exists, 403);

        $validated = $this->validateLedgerData($request, $client->id);
        $validated['client_id']    = $client->id;
        $validated['submitted_by'] = Auth::id();

        if ($validated['status'] === 'submitted') {
            $validated['submitted_at'] = now();
        }

        $ledger = MonthlyLedger::create($validated);

        $this->syncBankBalances($ledger, $request->input('bank_balances', []));

        return back()->with('success', 'Ledger entry saved for ' . $validated['eth_month'] . ' ' . $validated['eth_year'] . '.');
    }

    public function update(Request $request, MonthlyLedger $ledger)
    {
        $user = Auth::user();

        // Verified entries can only be re-opened by managers/admins
        if ($ledger->status === 'verified') {
            abort_unless($user->hasAnyRole(['Super Admin', 'Branch Manager']), 403, 'Only a manager can edit a verified entry.');
        }

        $validated = $this->validateLedgerData($request, $ledger->client_id, $ledger);

        if ($validated['status'] === 'submitted' && $ledger->status === 'draft') {
            $validated['submitted_at'] = now();
            $validated['submitted_by'] = Auth::id();
        }

        $ledger->update($validated);

        $this->syncBankBalances($ledger, $request->input('bank_balances', []));

        return back()->with('success', 'Entry updated successfully.');
    }

    public function downloadPdf(MonthlyLedger $ledger)
    {
        $ledger->loadMissing(['client', 'submittedBy', 'verifiedBy', 'bankAccountBalances.bankAccount']);

        $fmt = fn ($v) => number_format((float) $v, 2);

        $pdf = Pdf::loadView('exports.ledger-report', compact('ledger', 'fmt'))
            ->setPaper('a4', 'portrait');

        $filename = 'Ledger_' . preg_replace('/\s+/', '_', $ledger->client->company_name)
            . '_' . $ledger->eth_month . '_' . $ledger->eth_year . '.pdf';

        return $pdf->download($filename);
    }

    public function downloadXlsx(MonthlyLedger $ledger)
    {
        $filename = 'Ledger_' . preg_replace('/\s+/', '_', $ledger->client->company_name)
            . '_' . $ledger->eth_month . '_' . $ledger->eth_year . '.xlsx';

        return Excel::download(new LedgerReportExport($ledger), $filename);
    }

    public function verify(MonthlyLedger $ledger)
    {
        abort_unless(Auth::user()->hasAnyRole(['Super Admin', 'Branch Manager']), 403);

        $ledger->update([
            'status'      => 'verified',
            'verified_by' => Auth::id(),
            'verified_at' => now(),
        ]);

        return back()->with('success', 'Entry verified.');
    }

    /**
     * Link (or update) the client's Google Sheet workbook. Accepts a full
     * sheet URL or a bare spreadsheet ID and stores the extracted ID.
     */
    public function linkSheet(Request $request, Client $client)
    {
        abort_unless($client->exists, 403);
        abort_unless(Auth::user()->hasAnyRole(['Super Admin', 'Operation Manager', 'Branch Manager']), 403);

        $validated = $request->validate([
            'google_sheet_id' => 'nullable|string|max:255',
        ]);

        $raw = trim((string) ($validated['google_sheet_id'] ?? ''));

        if ($raw === '') {
            $client->update(['google_sheet_id' => null]);
            return back()->with('success', 'Google Sheet unlinked.');
        }

        // Extract the ID from a pasted URL like
        // https://docs.google.com/spreadsheets/d/<ID>/edit
        if (preg_match('#/spreadsheets/d/([a-zA-Z0-9-_]+)#', $raw, $m)) {
            $raw = $m[1];
        }

        $client->update(['google_sheet_id' => $raw]);

        // Automatically lay down the entry template if the sheet is still empty,
        // so staff get the correct columns without any manual setup.
        $applied = $this->maybeAutoApplyTemplate($client);

        $msg = $applied
            ? 'Google Sheet linked and the entry template was applied automatically.'
            : 'Google Sheet linked. Use "Apply Template" to set up the entry columns.';

        return back()->with('success', $msg);
    }

    /**
     * Write the system entry template (header + month rows) into the linked
     * Google Sheet. Requires the service account to have Editor access.
     */
    public function applySheetTemplate(Client $client)
    {
        abort_unless($client->exists, 403);

        if (empty($client->google_sheet_id)) {
            return back()->with('error', 'Link a Google Sheet first, then apply the template.');
        }

        try {
            $sheets = new GoogleSheetsClient();
            $sheets->applyTemplate(
                $client->google_sheet_id,
                config('ledger_sheet.tab', 'Ledger'),
                LedgerSheetTemplateExport::templateRows($this->currentEthYear())
            );
        } catch (\Throwable $e) {
            return back()->with('error', 'Could not apply template: ' . $e->getMessage());
        }

        return back()->with('success', 'Entry template applied to the sheet. Staff can now fill in the monthly figures.');
    }

    /** Best-effort auto-apply on link; never blocks linking if it fails. */
    private function maybeAutoApplyTemplate(Client $client): bool
    {
        try {
            $sheets = new GoogleSheetsClient();
            $tab    = config('ledger_sheet.tab', 'Ledger');

            if (! $sheets->tabIsEmptyOrMissing($client->google_sheet_id, $tab)) {
                return false; // don't clobber existing data
            }

            $sheets->applyTemplate(
                $client->google_sheet_id,
                $tab,
                LedgerSheetTemplateExport::templateRows($this->currentEthYear())
            );

            return true;
        } catch (\Throwable $e) {
            report($e);
            return false;
        }
    }

    /**
     * Download the system-compatible entry template (XLSX) for this client.
     * Staff import it into Google Sheets to guarantee the sync can read it.
     */
    public function downloadSheetTemplate(Client $client)
    {
        abort_unless($client->exists, 403);

        $filename = 'Ledger_Template_' . preg_replace('/\s+/', '_', $client->company_name)
            . '_' . $this->currentEthYear() . '.xlsx';

        return Excel::download(
            new LedgerSheetTemplateExport($client, $this->currentEthYear()),
            $filename
        );
    }

    /**
     * Pull raw figures from the linked Google Sheet into MonthlyLedger rows.
     */
    public function syncSheet(Client $client, LedgerSheetSyncService $sync)
    {
        abort_unless($client->exists, 403);

        try {
            $result = $sync->sync($client);
        } catch (\Throwable $e) {
            return back()->with('error', 'Sheet sync failed: ' . $e->getMessage());
        }

        $msg = "Sheet synced — {$result['created']} added, {$result['updated']} updated, {$result['skipped']} skipped.";
        if (! empty($result['errors'])) {
            $msg .= ' Issues: ' . implode(' ', array_slice($result['errors'], 0, 5));
        }

        return back()->with(empty($result['errors']) ? 'success' : 'warning', $msg);
    }

    public function storeBankAccount(Request $request, Client $client)
    {
        abort_unless($client->exists, 403);

        $validated = $request->validate([
            'bank_name'      => 'required|string|max:100',
            'account_number' => 'required|string|max:50',
            'account_type'   => 'required|in:current,savings,overdraft,lc',
        ]);

        BankAccount::create(array_merge($validated, ['client_id' => $client->id]));

        return back()->with('success', 'Bank account added.');
    }

    // ── Private Helpers ──

    private function validateLedgerData(Request $request, int $clientId, ?MonthlyLedger $existing = null): array
    {
        $monthRule = Rule::in(MonthlyLedger::ethiopianMonths());
        $excludeId = $existing?->id;

        // Cross-month overlap rule for sales-document number ranges
        $overlapRule = function (string $startCol, string $endCol) use ($clientId, $excludeId, $request) {
            return function ($attribute, $value, $fail) use ($startCol, $endCol, $clientId, $excludeId, $request) {
                $start = $request->input($startCol);
                $end   = $request->input($endCol);
                if ($start === null || $end === null) return;

                $conflict = MonthlyLedger::withoutGlobalScopes()
                    ->where('client_id', $clientId)
                    ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
                    ->whereNotNull($startCol)->whereNotNull($endCol)
                    ->where(function ($q) use ($startCol, $endCol, $start, $end) {
                        // Two ranges overlap iff start <= other.end AND end >= other.start
                        $q->where($startCol, '<=', $end)->where($endCol, '>=', $start);
                    })
                    ->first(['eth_year', 'eth_month', $startCol, $endCol]);

                if ($conflict) {
                    $fail("Range overlaps with {$conflict->eth_month} {$conflict->eth_year} ({$conflict->$startCol}–{$conflict->$endCol}).");
                }
            };
        };

        // Inventory sold cannot exceed available units (start + purchases)
        $inventoryRule = function ($attribute, $value, $fail) use ($request) {
            if ($value === null || $value === '') return;
            $start     = (float) ($request->input('inventory_items_start') ?? 0);
            $purchases = (float) ($request->input('purchases') ?? 0);
            if ((float) $value > $start + $purchases) {
                $fail('Sold quantity cannot exceed beginning inventory + purchases.');
            }
        };

        return $request->validate([
            'eth_year'  => 'required|integer|min:2000|max:2100',
            'eth_month' => ['required', $monthRule],
            'status'    => 'required|in:draft,submitted',

            'cash_machine_sales'  => 'nullable|numeric|min:0',
            'manual_sales'        => 'nullable|numeric|min:0',

            // Document-number audit trail
            'cash_machine_start_number' => ['nullable', 'integer', 'min:0', 'required_with:cash_machine_end_number'],
            'cash_machine_end_number'   => ['nullable', 'integer', 'min:0', 'required_with:cash_machine_start_number', 'gte:cash_machine_start_number', $overlapRule('cash_machine_start_number', 'cash_machine_end_number')],
            'manual_receipt_start_number' => ['nullable', 'integer', 'min:0', 'required_with:manual_receipt_end_number'],
            'manual_receipt_end_number'   => ['nullable', 'integer', 'min:0', 'required_with:manual_receipt_start_number', 'gte:manual_receipt_start_number', $overlapRule('manual_receipt_start_number', 'manual_receipt_end_number')],

            'beginning_inventory' => 'nullable|numeric|min:0',
            'purchases'           => 'nullable|numeric|min:0',
            'ending_inventory'    => 'nullable|numeric|min:0',

            // Inventory unit-level tracking
            'inventory_items_start'   => 'nullable|numeric|min:0',
            'inventory_items_end'     => 'nullable|numeric|min:0',
            'inventory_sold_quantity' => ['nullable', 'numeric', 'min:0', $inventoryRule],

            'salary_expense'             => 'nullable|numeric|min:0',
            'pension_expense'            => 'nullable|numeric|min:0',
            'printing_expense'           => 'nullable|numeric|min:0',
            'shed_rent'                  => 'nullable|numeric|min:0',
            'stationery_expense'         => 'nullable|numeric|min:0',
            'office_rent_expense'        => 'nullable|numeric|min:0',
            'transport_expense'          => 'nullable|numeric|min:0',
            'machine_fa_expense'         => 'nullable|numeric|min:0',
            'eeu_expense'                => 'nullable|numeric|min:0',
            'maintenance_expense'        => 'nullable|numeric|min:0',
            'advertising_expense'        => 'nullable|numeric|min:0',
            'uniform_expense'            => 'nullable|numeric|min:0',
            'indirect_materials_expense' => 'nullable|numeric|min:0',
            'depreciation_expense'       => 'nullable|numeric|min:0',
            'legal_fee_expense'          => 'nullable|numeric|min:0',
            'bank_interest_expense'      => 'nullable|numeric|min:0',
            'bank_service_charge'        => 'nullable|numeric|min:0',

            'sales_vat'       => 'nullable|numeric|min:0',
            'purchase_vat'    => 'nullable|numeric|min:0',
            'withholding_tax' => 'nullable|numeric|min:0',

            'notes'    => 'nullable|string|max:1000',
            'tax_rate' => 'nullable|numeric|min:0|max:100',

            'custom_expenses'              => 'nullable|array|max:20',
            'custom_expenses.*.label'      => 'required|string|max:100',
            'custom_expenses.*.amount'     => 'required|numeric|min:0',

            'hidden_expense_fields'   => 'nullable|array',
            'hidden_expense_fields.*' => 'string|max:80',
        ]);
    }

    private function syncBankBalances(MonthlyLedger $ledger, array $bankBalances): void
    {
        foreach ($bankBalances as $accountId => $data) {
            BankAccountBalance::updateOrCreate(
                ['bank_account_id' => $accountId, 'monthly_ledger_id' => $ledger->id],
                [
                    'balance'          => $data['balance'] ?? 0,
                    'loan_amount'      => $data['loan_amount'] ?? 0,
                    'lc_margin_release'=> $data['lc_margin_release'] ?? 0,
                    'transfer_in'      => $data['transfer_in'] ?? 0,
                    'transfer_reversal'=> $data['transfer_reversal'] ?? 0,
                ]
            );
        }
    }

    private function currentEthYear(): int
    {
        // Ethiopian year is ~7-8 years behind Gregorian.
        // New Ethiopian year starts ~11 Sep (Gregorian).
        $now = now();
        $ethYear = $now->year - 7;
        if ($now->month < 9 || ($now->month === 9 && $now->day < 11)) {
            $ethYear--;
        }
        return $ethYear;
    }
}
