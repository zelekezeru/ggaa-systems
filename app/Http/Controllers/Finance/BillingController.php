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

        $recentPayments = ServiceInvoicePayment::with(['invoice.client', 'recordedBy'])
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

        return Inertia::render('Finance/Billing', [
            'kpis'            => $kpis,
            'clientsBilling'  => $clients->values(),
            'recentPayments'  => $recentPayments,
            'pendingInvoices' => $pendingInvoices,
        ]);
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
        ]);

        // Handle file upload
        $photoPath = null;
        if ($request->hasFile('receipt_photo')) {
            $photoPath = $request->file('receipt_photo')->store('receipts', 'public');
        }

        // Find or create an invoice for this month if one doesn't exist?
        // For simplicity, let's assume we link to the latest unpaid invoice or just record the payment
        $invoice = ServiceInvoice::where('client_id', $client->id)
            ->where('status', '!=', 'paid')
            ->orderBy('created_at', 'desc')
            ->first();

        // If no invoice found, create a generic one for the retainer?
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
            'status'             => 'Completed',
        ]);

        // Update client status
        $client->update([
            'payment_status'    => 'Paid',
            'last_payment_date' => $validated['payment_date'],
        ]);

        return back()->with('success', "Payment of ETB " . number_format($validated['amount'], 2) . " recorded for {$client->company_name}.");
    }
}
