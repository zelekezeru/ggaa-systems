<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\ServiceInvoice;
use App\Models\ServiceInvoicePayment;
use App\Models\User;
use App\Notifications\VatDeadlineReminder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class BillingController extends Controller
{
    public function index()
    {
        $clients = Client::select(
            'id',
            'company_name',
            'retainer_fee',
            'last_payment_date',
            'payment_status',
            'last_reminder_sent_at'
        )->get();

        $recentPayments = ServiceInvoicePayment::with(['invoice.client', 'recordedBy', 'approvedBy'])
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        $pendingInvoices = ServiceInvoice::with('client')
            ->where('status', '!=', 'paid')
            ->orderBy('due_date', 'asc')
            ->get();

        $overdueCount = $clients->filter(function ($c) {
            return $c->payment_status !== 'Paid'
                && $c->last_payment_date
                && now()->diffInDays($c->last_payment_date, false) < -30;
        })->count();

        $kpis = [
            'total_expected'  => number_format($clients->sum('retainer_fee'), 2),
            'total_overdue'   => number_format($overdueCount * 500, 2), // Placeholder logic or specific count
            'total_collected' => number_format($recentPayments->sum('amount'), 2),
            'pending_count'   => $pendingInvoices->count(),
        ];

        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        
        return Inertia::render('Finance/Billing', [
            'kpis'            => $kpis,
            'clientsBilling'  => $clients->values(),
            'recentPayments'  => $recentPayments,
            'pendingInvoices' => $pendingInvoices,
            'canApprove'      => $user && $user->hasAnyRole(['Super Admin', 'Finance Admin']),
            'canRecord'       => $user && $user->hasAnyRole(['Super Admin', 'Finance Admin', 'Branch Manager', 'Employee']),
            'draftPayments'   => ServiceInvoicePayment::with(['invoice.client', 'recordedBy'])
                                    ->where('status', 'Draft')
                                    ->get(),
        ]);
    }

    public function generateExpectedPayments(Request $request)
    {
        $request->validate([
            'month' => 'required|integer|between:1,12',
            'year'  => 'required|integer|min:2020',
        ]);

        $clients = Client::where('retainer_fee', '>', 0)->get();
        $count = 0;

        foreach ($clients as $client) {
            // Check if already has a draft or payment for this month/year range
            // For simplicity, we check if an invoice for this month exists or create one
            $start = Carbon::create($request->year, $request->month, 1)->startOfMonth();
            $end = $start->copy()->endOfMonth();

            $invoice = ServiceInvoice::where('client_id', $client->id)
                ->where('period_start', $start)
                ->first();

            if (!$invoice) {
                $invoice = ServiceInvoice::create([
                    'client_id'      => $client->id,
                    'invoice_number' => ServiceInvoice::generateInvoiceNumber(),
                    'amount'         => $client->retainer_fee,
                    'status'         => 'draft',
                    'period_start'   => $start,
                    'period_end'     => $end,
                    'due_date'       => $end->copy()->addDays(5),
                    'created_by'     => Auth::id(),
                ]);
            }

            // Create Draft payment if none exists for this invoice
            if (!$invoice->payments()->where('status', 'Draft')->exists() && !$invoice->payments()->where('status', 'Completed')->exists()) {
                ServiceInvoicePayment::create([
                    'service_invoice_id' => $invoice->id,
                    'amount'             => $client->retainer_fee,
                    'payment_method'     => 'bank_transfer',
                    'status'             => 'Draft',
                    'recorded_by'        => Auth::id(),
                    'scheduled_at'       => now(),
                    'notes'              => "Auto-generated expected payment for {$start->format('F Y')}",
                ]);
                $count++;
            }
        }

        return back()->with('success', "Generated {$count} expected draft payments for " . $start->format('F Y'));
    }

    public function sendReminder(Request $request, Client $client)
    {
        $clientUser = User::where('client_id', $client->id)->first();

        if ($clientUser) {
            $task = $client->tasks()->first();
            if ($task) {
                $clientUser->notify(new VatDeadlineReminder($task));
            }
        }

        $client->update(['last_reminder_sent_at' => now()]);

        return back()->with('success', "Reminder sent to {$client->company_name}.");
    }

    public function recordPayment(Request $request, Client $client)
    {
        $validated = $request->validate([
            'amount'          => ['required', 'numeric', 'min:0'],
            'payment_method'  => ['required', 'string'],
            'payment_date'    => ['required', 'date', 'before_or_equal:today'],
            'reference'       => ['nullable', 'string', 'max:100'],
            'receipt_link'    => ['nullable', 'url', 'max:255'],
            'receipt_photo'   => ['nullable', 'image', 'max:2048'], // 2MB max
            'notes'           => ['nullable', 'string', 'max:500'],
            'status'          => ['nullable', 'string', 'in:Draft,Pending Approval,Completed'],
        ]);

        // Handle file upload
        $photoPath = null;
        if ($request->hasFile('receipt_photo')) {
            $photoPath = $request->file('receipt_photo')->store('receipts', 'public');
        }

        $invoice = ServiceInvoice::where('client_id', $client->id)
            ->where('status', '!=', 'paid')
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$invoice) {
            $invoice = ServiceInvoice::create([
                'client_id'      => $client->id,
                'invoice_number' => ServiceInvoice::generateInvoiceNumber(),
                'amount'         => $validated['amount'],
                'status'         => 'draft',
                'period_start'   => now()->startOfMonth(),
                'period_end'     => now()->endOfMonth(),
                'due_date'       => now()->addDays(7),
                'created_by'     => Auth::id(),
            ]);
        }

        $paymentStatus = $validated['status'] ?? 'Completed';

        $payment = ServiceInvoicePayment::create([
            'service_invoice_id' => $invoice->id,
            'amount'             => $validated['amount'],
            'payment_method'     => $validated['payment_method'],
            'reference'          => $validated['reference'],
            'receipt_link'       => $validated['receipt_link'],
            'receipt_photo_path' => $photoPath,
            'paid_at'            => $validated['payment_date'],
            'recorded_by'        => Auth::id(),
            'notes'              => $validated['notes'],
            'status'             => $paymentStatus,
            'scheduled_at'       => $paymentStatus === 'Draft' ? now() : null,
        ]);

        // Only update client status if payment is completed
        if ($paymentStatus === 'Completed') {
            $client->update([
                'payment_status'    => 'Paid',
                'last_payment_date' => $validated['payment_date'],
            ]);
        }

        return back()->with('success', "Payment of ETB " . number_format($validated['amount'], 2) . " ($paymentStatus) recorded for {$client->company_name}.");
    }

    public function approvePayment(ServiceInvoicePayment $payment)
    {
        // $this->authorize('approve payments'); // Handled by role check usually

        $payment->update([
            'status'      => 'Completed',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);

        // Recompute invoice status
        $payment->invoice->recomputeStatus();

        // Update client status if invoice is fully paid
        if ($payment->invoice->status === 'paid') {
            $payment->invoice->client->update([
                'payment_status'    => 'Paid',
                'last_payment_date' => $payment->paid_at ?? now(),
            ]);
        }

        return back()->with('success', "Payment of ETB " . number_format($payment->amount, 2) . " approved.");
    }

    public function submitDraftPayment(Request $request, ServiceInvoicePayment $payment)
    {
        $validated = $request->validate([
            'amount'          => ['required', 'numeric', 'min:0'],
            'payment_method'  => ['required', 'string'],
            'paid_at'         => ['required', 'date', 'before_or_equal:today'],
            'reference'       => ['nullable', 'string', 'max:100'],
            'receipt_link'    => ['nullable', 'url', 'max:255'],
            'receipt_photo'   => ['nullable', 'image', 'max:2048'],
            'notes'           => ['nullable', 'string', 'max:500'],
        ]);

        $photoPath = $payment->receipt_photo_path;
        if ($request->hasFile('receipt_photo')) {
            $photoPath = $request->file('receipt_photo')->store('receipts', 'public');
        }

        $payment->update([
            'amount'             => $validated['amount'],
            'payment_method'     => $validated['payment_method'],
            'reference'          => $validated['reference'],
            'receipt_link'       => $validated['receipt_link'],
            'receipt_photo_path' => $photoPath,
            'paid_at'            => $validated['paid_at'],
            'notes'              => $validated['notes'],
            'status'             => 'Pending Approval',
        ]);

        return back()->with('success', "Draft payment submitted for approval.");
    }

    public function rejectPayment(Request $request, ServiceInvoicePayment $payment)
    {
        $request->validate(['reason' => 'required|string|max:500']);

        $payment->update([
            'status'      => 'Rejected',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
            'notes'       => ($payment->notes ? $payment->notes . "\n\n" : "") . "Rejected: " . $request->reason,
        ]);

        return back()->with('success', "Payment rejected.");
    }
}
