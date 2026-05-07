<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\MonthlyLedger;
use App\Models\ServiceInvoice;
use App\Models\ServiceInvoicePayment;
use App\Models\Task;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        $invoices = ServiceInvoice::with(['client:id,company_name,tin_number', 'creator:id,name'])
            ->withCount('payments')
            ->latest()
            ->get()
            ->map(fn ($i) => array_merge($i->toArray(), [
                'paid_amount' => $i->paid_amount,
                'balance_due' => $i->balance_due,
            ]));

        $kpis = [
            'total_billed'    => (float) ServiceInvoice::sum('amount'),
            'total_collected' => (float) ServiceInvoicePayment::sum('amount'),
            'outstanding'     => (float) ServiceInvoice::whereIn('status', ['sent', 'partially_paid', 'overdue'])->sum('amount')
                                  - (float) ServiceInvoicePayment::whereHas('invoice', fn ($q) => $q->whereIn('status', ['sent', 'partially_paid', 'overdue']))->sum('amount'),
            'count_open'      => ServiceInvoice::whereIn('status', ['draft', 'sent', 'partially_paid', 'overdue'])->count(),
        ];

        return Inertia::render('Finance/Invoices/Index', [
            'invoices' => $invoices,
            'kpis'     => $kpis,
        ]);
    }

    public function create(Request $request)
    {
        $clients = Client::withoutGlobalScopes()
            ->select(['id', 'company_name', 'tin_number', 'retainer_fee'])
            ->orderBy('company_name')
            ->get();

        return Inertia::render('Finance/Invoices/Create', [
            'clients' => $clients,
        ]);
    }

    public function servicesRendered(Request $request)
    {
        $request->validate([
            'client_id'    => 'required|exists:clients,id',
            'period_start' => 'required|date',
            'period_end'   => 'required|date|after_or_equal:period_start',
        ]);

        $start = Carbon::parse($request->period_start)->startOfDay();
        $end   = Carbon::parse($request->period_end)->endOfDay();

        $tasks = Task::withoutGlobalScopes()
            ->where('client_id', $request->client_id)
            ->where('status', 'Done')
            ->whereBetween('completed_at', [$start, $end])
            ->get(['id', 'title', 'completed_at']);

        $ledgers = MonthlyLedger::withoutGlobalScopes()
            ->where('client_id', $request->client_id)
            ->where('status', 'verified')
            ->whereBetween('verified_at', [$start, $end])
            ->get(['id', 'eth_year', 'eth_month', 'verified_at']);

        return response()->json([
            'tasks'   => $tasks->map(fn ($t) => [
                'type'         => 'task',
                'id'           => $t->id,
                'name'         => $t->title,
                'completed_at' => $t->completed_at,
            ]),
            'ledgers' => $ledgers->map(fn ($l) => [
                'type'         => 'ledger',
                'id'           => $l->id,
                'name'         => "Monthly Ledger — {$l->eth_month} {$l->eth_year}",
                'completed_at' => $l->verified_at,
            ]),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'client_id'    => 'required|exists:clients,id',
            'period_start' => 'required|date',
            'period_end'   => 'required|date|after_or_equal:period_start',
            'amount'       => 'required|numeric|min:0',
            'description'  => 'nullable|string|max:500',
            'due_date'     => 'nullable|date|after_or_equal:today',
            'send_now'     => 'nullable|boolean',
            'services'     => 'nullable|array',
        ]);

        $invoice = ServiceInvoice::create([
            'invoice_number'    => ServiceInvoice::generateInvoiceNumber(),
            'client_id'         => $validated['client_id'],
            'period_start'      => $validated['period_start'],
            'period_end'        => $validated['period_end'],
            'amount'            => $validated['amount'],
            'description'       => $validated['description'] ?? null,
            'due_date'          => $validated['due_date'] ?? null,
            'services_snapshot' => $validated['services'] ?? [],
            'status'            => ($validated['send_now'] ?? false) ? 'sent' : 'draft',
            'issued_at'         => ($validated['send_now'] ?? false) ? now() : null,
            'created_by'        => Auth::id(),
        ]);

        return redirect()->route('finance.invoices.show', $invoice->id)
            ->with('success', 'Invoice ' . $invoice->invoice_number . ' created.');
    }

    public function show(ServiceInvoice $invoice)
    {
        $invoice->load(['client', 'payments.recordedBy:id,name', 'creator:id,name']);

        return Inertia::render('Finance/Invoices/Show', [
            'invoice'     => array_merge($invoice->toArray(), [
                'paid_amount' => $invoice->paid_amount,
                'balance_due' => $invoice->balance_due,
            ]),
        ]);
    }

    public function send(ServiceInvoice $invoice)
    {
        if ($invoice->status === 'draft') {
            $invoice->update([
                'status'    => 'sent',
                'issued_at' => now(),
            ]);
        }
        return back()->with('success', 'Invoice sent to client.');
    }

    public function recordPayment(Request $request, ServiceInvoice $invoice)
    {
        $validated = $request->validate([
            'amount'         => 'required|numeric|min:0.01|max:' . max(0.01, $invoice->balance_due),
            'payment_method' => 'required|in:cash,bank_transfer,check,mobile_money,other',
            'reference'      => 'nullable|string|max:120',
            'paid_at'        => 'required|date|before_or_equal:today',
            'notes'          => 'nullable|string|max:500',
        ]);

        $invoice->payments()->create(array_merge($validated, ['recorded_by' => Auth::id()]));

        return back()->with('success', 'Payment of ETB ' . number_format($validated['amount'], 2) . ' recorded.');
    }

    public function downloadPdf(ServiceInvoice $invoice)
    {
        $invoice->load(['client', 'payments', 'creator:id,name']);

        $fmt = fn ($v) => number_format((float) $v, 2);

        $pdf = Pdf::loadView('exports.service-invoice', compact('invoice', 'fmt'))
            ->setPaper('a4', 'portrait');

        return $pdf->download($invoice->invoice_number . '.pdf');
    }

    public function destroy(ServiceInvoice $invoice)
    {
        if ($invoice->payments()->exists()) {
            return back()->withErrors(['delete' => 'Cannot delete an invoice with recorded payments. Cancel it instead.']);
        }
        $invoice->delete();
        return redirect()->route('finance.invoices.index')->with('success', 'Invoice deleted.');
    }

    public function cancel(ServiceInvoice $invoice)
    {
        $invoice->update(['status' => 'cancelled']);
        return back()->with('success', 'Invoice cancelled.');
    }

    public function approvePayment(ServiceInvoicePayment $payment)
    {
        $payment->update([
            'status'      => 'Completed',
            'approved_at' => now(),
            'approved_by' => Auth::id(),
        ]);
        return back()->with('success', 'Payment approved.');
    }

    public function rejectPayment(Request $request, ServiceInvoicePayment $payment)
    {
        $request->validate(['reason' => 'nullable|string|max:300']);

        $payment->update([
            'status'      => 'Rejected',
            'approved_at' => now(),
            'approved_by' => Auth::id(),
            'notes'       => trim(($payment->notes ? $payment->notes . "\n\n" : '') . 'Rejected: ' . ($request->reason ?? 'No reason provided.')),
        ]);
        return back()->with('success', 'Payment rejected.');
    }
}
