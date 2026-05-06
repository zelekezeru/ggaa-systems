<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>{{ $invoice->invoice_number }} — {{ $invoice->client->company_name }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1f2937; }
        .header { display: table; width: 100%; margin-bottom: 20px; }
        .header .col { display: table-cell; vertical-align: top; }
        .header .right { text-align: right; }
        h1 { font-size: 20px; margin: 0; color: #1e3a8a; letter-spacing: 1px; }
        .firm { font-size: 13px; font-weight: bold; color: #1e3a8a; }
        .meta { font-size: 10px; color: #6b7280; }
        .badge { display: inline-block; padding: 3px 10px; border-radius: 12px; font-size: 9px; font-weight: bold; text-transform: uppercase; }
        .badge.paid { background: #d1fae5; color: #065f46; }
        .badge.partially_paid { background: #fef3c7; color: #92400e; }
        .badge.sent { background: #dbeafe; color: #1e40af; }
        .badge.overdue { background: #fee2e2; color: #991b1b; }
        .badge.draft { background: #f3f4f6; color: #4b5563; }
        .badge.cancelled { background: #f3f4f6; color: #6b7280; text-decoration: line-through; }
        section { margin-bottom: 16px; }
        h2 { font-size: 11px; padding: 5px 10px; margin: 0 0 6px; background: #f3f4f6; color: #1e3a8a; border-left: 3px solid #1e3a8a; }
        table { width: 100%; border-collapse: collapse; }
        td, th { padding: 6px 10px; border-bottom: 1px solid #e5e7eb; }
        th { background: #f9fafb; text-align: left; font-size: 10px; text-transform: uppercase; color: #4b5563; }
        td.amt, th.amt { text-align: right; font-variant-numeric: tabular-nums; }
        .totals { width: 50%; margin-left: 50%; margin-top: 12px; }
        .totals td { border-bottom: 0; padding: 4px 10px; }
        .totals tr.grand td { background: #1e3a8a; color: #fff; font-weight: bold; font-size: 13px; }
        .totals tr.balance td { background: #fef3c7; font-weight: bold; color: #92400e; }
        .footer { margin-top: 30px; font-size: 10px; color: #6b7280; border-top: 1px solid #e5e7eb; padding-top: 10px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="col">
            <div class="firm">GGAA Systems</div>
            <div class="meta">Accounting Services<br>Addis Ababa, Ethiopia</div>
        </div>
        <div class="col right">
            <h1>INVOICE</h1>
            <div class="meta">
                #{{ $invoice->invoice_number }}<br>
                Issued: {{ $invoice->issued_at?->format('Y-m-d') ?? 'Draft' }}<br>
                @if ($invoice->due_date) Due: {{ $invoice->due_date->format('Y-m-d') }}<br> @endif
                <span class="badge {{ $invoice->status }}">{{ str_replace('_', ' ', $invoice->status) }}</span>
            </div>
        </div>
    </div>

    <section>
        <h2>Bill To</h2>
        <strong>{{ $invoice->client->company_name }}</strong><br>
        @if ($invoice->client->tin_number) <span class="meta">TIN: {{ $invoice->client->tin_number }}</span><br> @endif
    </section>

    <section>
        <h2>Service Period</h2>
        {{ $invoice->period_start->format('Y-m-d') }} — {{ $invoice->period_end->format('Y-m-d') }}
        @if ($invoice->description)
            <br><span class="meta">{{ $invoice->description }}</span>
        @endif
    </section>

    @if (!empty($invoice->services_snapshot))
        <section>
            <h2>Services Rendered</h2>
            <table>
                <thead>
                    <tr><th style="width:80px">Type</th><th>Description</th><th style="width:120px">Completed</th></tr>
                </thead>
                <tbody>
                    @foreach ($invoice->services_snapshot as $svc)
                        <tr>
                            <td>{{ ucfirst($svc['type'] ?? '') }}</td>
                            <td>{{ $svc['name'] ?? '' }}</td>
                            <td>{{ isset($svc['completed_at']) ? \Carbon\Carbon::parse($svc['completed_at'])->format('Y-m-d') : '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </section>
    @endif

    <section>
        <h2>Charges</h2>
        <table>
            <thead><tr><th>Description</th><th class="amt" style="width:140px">Amount (ETB)</th></tr></thead>
            <tbody>
                <tr>
                    <td>{{ $invoice->description ?: 'Professional services rendered for the period above' }}</td>
                    <td class="amt">{{ $fmt($invoice->amount) }}</td>
                </tr>
            </tbody>
        </table>

        <table class="totals">
            <tr class="grand"><td>Total</td><td class="amt">ETB {{ $fmt($invoice->amount) }}</td></tr>
            <tr><td>Paid to date</td><td class="amt">ETB {{ $fmt($invoice->paid_amount) }}</td></tr>
            <tr class="balance"><td>Balance Due</td><td class="amt">ETB {{ $fmt($invoice->balance_due) }}</td></tr>
        </table>
    </section>

    @if ($invoice->payments->count() > 0)
        <section>
            <h2>Payment History</h2>
            <table>
                <thead><tr><th style="width:100px">Date</th><th style="width:140px">Method</th><th>Reference</th><th class="amt" style="width:120px">Amount</th></tr></thead>
                <tbody>
                    @foreach ($invoice->payments as $p)
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($p->paid_at)->format('Y-m-d') }}</td>
                            <td>{{ str_replace('_', ' ', $p->payment_method) }}</td>
                            <td>{{ $p->reference ?? '—' }}</td>
                            <td class="amt">{{ $fmt($p->amount) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </section>
    @endif

    <div class="footer">
        Prepared by {{ $invoice->creator?->name ?? '—' }} · Generated {{ now()->format('Y-m-d H:i') }}
    </div>
</body>
</html>
