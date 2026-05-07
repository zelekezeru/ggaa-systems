<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Monthly Ledger — {{ $ledger->client->company_name }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1f2937; }
        h1 { font-size: 18px; margin: 0 0 4px; color: #1e3a8a; }
        .meta { font-size: 10px; color: #6b7280; margin-bottom: 12px; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 10px; font-size: 9px; font-weight: bold; text-transform: uppercase; }
        .badge.verified { background: #d1fae5; color: #065f46; }
        .badge.submitted { background: #dbeafe; color: #1e40af; }
        .badge.draft { background: #fef3c7; color: #92400e; }
        section { margin-bottom: 14px; }
        h2 { font-size: 12px; padding: 4px 8px; margin: 0 0 6px; color: #fff; }
        h2.sales { background: #2563eb; }
        h2.cogs { background: #ea580c; }
        h2.exp { background: #7c3aed; }
        h2.vat { background: #0d9488; }
        h2.bank { background: #4f46e5; }
        h2.net { background: #059669; }
        table { width: 100%; border-collapse: collapse; }
        td { padding: 3px 8px; border-bottom: 1px solid #e5e7eb; }
        td.label { color: #4b5563; }
        td.amt { text-align: right; font-variant-numeric: tabular-nums; }
        tr.total td { font-weight: bold; background: #f3f4f6; }
        tr.grand td { font-weight: bold; font-size: 13px; background: #fef3c7; color: #92400e; }
        .sig { margin-top: 16px; font-size: 10px; color: #6b7280; }
        .sig span { display: inline-block; min-width: 200px; }
        thead td { font-weight: bold; background: #f9fafb; }
    </style>
</head>
<body>
    <h1>{{ $ledger->client->company_name }} — Monthly Financial Ledger</h1>
    <div class="meta">
        TIN: {{ $ledger->client->tin_number ?? '—' }} &nbsp;|&nbsp;
        Period: <strong>{{ $ledger->eth_month }} {{ $ledger->eth_year }}</strong> &nbsp;|&nbsp;
        <span class="badge {{ $ledger->status }}">{{ $ledger->status }}</span>
    </div>

    <section>
        <h2 class="sales">I. Sales Income</h2>
        <table>
            <tr><td class="label">Cash Register Machine Sales</td><td class="amt">{{ $fmt($ledger->cash_machine_sales) }}</td></tr>
            @if ($ledger->cash_machine_start_number !== null)
            <tr><td class="label" style="padding-left:20px;color:#6b7280">&#8627; Receipt # Start</td><td class="amt">{{ $ledger->cash_machine_start_number }}</td></tr>
            <tr><td class="label" style="padding-left:20px;color:#6b7280">&#8627; Receipt # End</td><td class="amt">{{ $ledger->cash_machine_end_number ?? '—' }}</td></tr>
            <tr><td class="label" style="padding-left:20px;color:#6b7280">&#8627; Receipt Count</td><td class="amt">{{ $ledger->cash_machine_sales_count }}</td></tr>
            @endif
            <tr><td class="label">Manual (Hand-to-Hand) Sales</td><td class="amt">{{ $fmt($ledger->manual_sales) }}</td></tr>
            @if ($ledger->manual_receipt_start_number !== null)
            <tr><td class="label" style="padding-left:20px;color:#6b7280">&#8627; Receipt # Start</td><td class="amt">{{ $ledger->manual_receipt_start_number }}</td></tr>
            <tr><td class="label" style="padding-left:20px;color:#6b7280">&#8627; Receipt # End</td><td class="amt">{{ $ledger->manual_receipt_end_number ?? '—' }}</td></tr>
            <tr><td class="label" style="padding-left:20px;color:#6b7280">&#8627; Receipt Count</td><td class="amt">{{ $ledger->manual_receipt_count }}</td></tr>
            @endif
            <tr class="total"><td>Total Sales</td><td class="amt">{{ $fmt($ledger->total_sales) }}</td></tr>
        </table>
    </section>

    <section>
        <h2 class="cogs">II. Cost of Goods Sold</h2>
        <table>
            <tr><td class="label">A) Beginning Inventory</td><td class="amt">{{ $fmt($ledger->beginning_inventory) }}</td></tr>
            <tr><td class="label">B) Purchases</td><td class="amt">{{ $fmt($ledger->purchases) }}</td></tr>
            <tr><td class="label">C = A+B) Available for Sales</td><td class="amt">{{ $fmt($ledger->available_for_sales) }}</td></tr>
            <tr><td class="label">D) Ending Inventory</td><td class="amt">{{ $fmt($ledger->ending_inventory) }}</td></tr>
            @if ($ledger->inventory_items_start !== null)
            <tr><td class="label" style="padding-left:20px;color:#6b7280">&#8627; Units at Start</td><td class="amt">{{ $ledger->inventory_items_start }}</td></tr>
            <tr><td class="label" style="padding-left:20px;color:#6b7280">&#8627; Units at End</td><td class="amt">{{ $ledger->inventory_items_end ?? '—' }}</td></tr>
            <tr><td class="label" style="padding-left:20px;color:#6b7280">&#8627; Units Sold</td><td class="amt">{{ $ledger->inventory_sold_quantity ?? '—' }}</td></tr>
            @endif
            <tr><td class="label">E = C&minus;D) Cost of Goods Sold</td><td class="amt">{{ $fmt($ledger->cost_of_goods_sold) }}</td></tr>
            <tr class="total"><td>F = Sales &minus; COGS) Gross Profit</td><td class="amt">{{ $fmt($ledger->gross_profit) }}</td></tr>
        </table>
    </section>

    <section>
        <h2 class="exp">III. Administration Expenses</h2>
        <table>
            @foreach ([
                'salary_expense' => 'Salary',
                'pension_expense' => 'Pension 11%',
                'eeu_expense' => 'EEU (Electricity)',
                'maintenance_expense' => 'Maintenance',
                'machine_fa_expense' => 'Machine / Fixed Asset',
                'office_rent_expense' => 'Office Rent',
                'shed_rent' => 'Shed Rent',
                'transport_expense' => 'Transportation',
                'printing_expense' => 'Printing',
                'stationery_expense' => 'Stationery',
                'advertising_expense' => 'Advertising',
                'uniform_expense' => 'Employee Uniform',
                'indirect_materials_expense' => 'Indirect Materials',
                'depreciation_expense' => 'Depreciation',
                'legal_fee_expense' => 'Legal Fee',
                'bank_interest_expense' => 'Bank Interest',
                'bank_service_charge' => 'Bank Service Charge',
            ] as $field => $label)
                <tr><td class="label">{{ $label }}</td><td class="amt">{{ $fmt($ledger->$field) }}</td></tr>
            @endforeach
            <tr class="total"><td>Total Expenses</td><td class="amt">{{ $fmt($ledger->total_expenses) }}</td></tr>
        </table>
    </section>

    <section>
        <h2 class="net">IV. Net Result</h2>
        <table>
            <tr class="grand"><td>Net Profit (NIBT)</td><td class="amt">{{ $fmt($ledger->net_profit) }}</td></tr>
            <tr><td class="label">Profit Tax (35% × NIBT − 24,600)</td><td class="amt">{{ $fmt($ledger->profit_tax) }}</td></tr>
        </table>
    </section>

    <section>
        <h2 class="vat">V. VAT Report</h2>
        <table>
            <tr><td class="label">Sales VAT (15%)</td><td class="amt">{{ $fmt($ledger->sales_vat) }}</td></tr>
            <tr><td class="label">Purchase VAT</td><td class="amt">{{ $fmt($ledger->purchase_vat) }}</td></tr>
            <tr><td class="label">Withholding Tax</td><td class="amt">{{ $fmt($ledger->withholding_tax) }}</td></tr>
        </table>
    </section>

    @if ($ledger->bankAccountBalances->count() > 0)
        <section>
            <h2 class="bank">VI. Bank Account Balances</h2>
            <table>
                <thead>
                    <tr>
                        <td>Bank</td><td>Account #</td>
                        <td class="amt">Balance</td><td class="amt">Loan</td>
                        <td class="amt">LC Margin</td><td class="amt">Transfer In</td>
                        <td class="amt">Reversal</td>
                    </tr>
                </thead>
                @foreach ($ledger->bankAccountBalances as $b)
                    <tr>
                        <td>{{ $b->bankAccount->bank_name ?? '—' }}</td>
                        <td>{{ $b->bankAccount->account_number ?? '—' }}</td>
                        <td class="amt">{{ $fmt($b->balance) }}</td>
                        <td class="amt">{{ $fmt($b->loan_amount) }}</td>
                        <td class="amt">{{ $fmt($b->lc_margin_release) }}</td>
                        <td class="amt">{{ $fmt($b->transfer_in) }}</td>
                        <td class="amt">{{ $fmt($b->transfer_reversal) }}</td>
                    </tr>
                @endforeach
                <tr class="total"><td colspan="2">Total Bank Balance</td><td class="amt">{{ $fmt($ledger->total_bank_balance) }}</td><td colspan="4"></td></tr>
            </table>
        </section>
    @endif

    @if ($ledger->notes)
        <section>
            <h2 class="exp">Notes</h2>
            <p>{{ $ledger->notes }}</p>
        </section>
    @endif

    <div class="sig">
        <span>Submitted by: <strong>{{ $ledger->submittedBy?->name ?? '—' }}</strong></span>
        <span>Verified by: <strong>{{ $ledger->verifiedBy?->name ?? '—' }}</strong></span>
        <span>Generated: {{ now()->format('Y-m-d H:i') }}</span>
    </div>
</body>
</html>
