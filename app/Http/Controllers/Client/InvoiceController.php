<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\ServiceInvoice;
use App\Models\ServiceInvoicePayment;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class InvoiceController extends Controller
{
    public function index()
    {
        // Global scope already restricts to client's own invoices
        $invoices = ServiceInvoice::with('payments:id,service_invoice_id,amount,paid_at')
            ->whereIn('status', ['sent', 'partially_paid', 'paid', 'overdue'])
            ->orderByDesc('issued_at')
            ->get()
            ->map(fn ($i) => [
                'id'              => $i->id,
                'invoice_number'  => $i->invoice_number,
                'period_start'    => $i->period_start,
                'period_end'      => $i->period_end,
                'amount'          => (float) $i->amount,
                'description'     => $i->description,
                'status'          => $i->status,
                'issued_at'       => $i->issued_at,
                'due_date'        => $i->due_date,
                'paid_amount'     => $i->paid_amount,
                'balance_due'     => $i->balance_due,
            ]);

        $kpis = [
            'total_billed'    => (float) $invoices->sum('amount'),
            'total_paid'      => (float) $invoices->sum('paid_amount'),
            'outstanding'     => (float) $invoices->sum('balance_due'),
            'open_count'      => $invoices->whereIn('status', ['sent', 'partially_paid', 'overdue'])->count(),
        ];

        return Inertia::render('Client/Invoices', [
            'invoices' => $invoices,
            'kpis'     => $kpis,
        ]);
    }

    public function payments()
    {
        // Use the invoice global scope: only payments tied to this client's invoices
        $payments = ServiceInvoicePayment::whereHas('invoice')
            ->with('invoice:id,invoice_number,amount,period_start,period_end')
            ->orderByDesc('paid_at')
            ->get()
            ->map(fn ($p) => [
                'id'             => $p->id,
                'amount'         => (float) $p->amount,
                'paid_at'        => $p->paid_at,
                'payment_method' => $p->payment_method,
                'reference'      => $p->reference,
                'status'         => $p->status ?? 'Completed',
                'invoice'        => $p->invoice ? [
                    'id'             => $p->invoice->id,
                    'invoice_number' => $p->invoice->invoice_number,
                    'amount'         => (float) $p->invoice->amount,
                    'period'         => $p->invoice->period_start->format('Y-m-d') . ' — ' . $p->invoice->period_end->format('Y-m-d'),
                ] : null,
            ]);

        // Fetch all the client's invoices once (scoped) to compute outstanding
        $invoices = ServiceInvoice::whereIn('status', ['sent', 'partially_paid', 'paid', 'overdue'])->get();

        $kpis = [
            'total_billed' => (float) $invoices->sum('amount'),
            'total_paid'   => (float) $payments->sum('amount'),
            'outstanding'  => (float) $invoices->sum(fn ($i) => $i->balance_due),
        ];

        return Inertia::render('Client/Payments', [
            'payments' => $payments,
            'kpis'     => $kpis,
        ]);
    }

    public function downloadPdf(ServiceInvoice $invoice)
    {
        $invoice->load(['client', 'payments', 'creator:id,name']);
        $fmt = fn ($v) => number_format((float) $v, 2);
        $pdf = Pdf::loadView('exports.service-invoice', compact('invoice', 'fmt'))->setPaper('a4', 'portrait');
        return $pdf->download($invoice->invoice_number . '.pdf');
    }

    public function submitPayment(Request $request, ServiceInvoice $invoice)
    {
        // Global scope ensures the invoice belongs to the authenticated client
        $validated = $request->validate([
            'amount'         => 'required|numeric|min:0.01|max:' . max(0.01, $invoice->balance_due),
            'payment_method' => 'required|in:cash,bank_transfer,check,mobile_money,card_payment,other',
            'reference'      => 'nullable|string|max:120',
            'paid_at'        => 'required|date|before_or_equal:today',
            'notes'          => 'nullable|string|max:500',
            'receipt'        => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        $receiptPath = $request->hasFile('receipt')
            ? $request->file('receipt')->store('invoice-receipts', 'public')
            : null;

        $invoice->payments()->create([
            'amount'             => $validated['amount'],
            'payment_method'     => $validated['payment_method'],
            'reference'          => $validated['reference'] ?? null,
            'paid_at'            => $validated['paid_at'],
            'notes'              => $validated['notes'] ?? null,
            'receipt_photo_path' => $receiptPath,
            'status'             => 'Pending',
            'recorded_by'        => Auth::id(),
        ]);

        return back()->with('success', 'Payment submitted for approval.');
    }
}
