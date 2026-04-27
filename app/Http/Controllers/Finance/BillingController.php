<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Notifications\VatDeadlineReminder;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BillingController extends Controller
{
    public function index()
    {
        // Global scope on Client does NOT restrict Finance Admins (no role match),
        // so this query returns all clients — which is correct for finance.
        $clients = Client::select(
            'id',
            'company_name',
            'retainer_fee',
            'last_payment_date',
            'payment_status'
        )->get();

        $overdue = $clients->filter(function ($c) {
            return $c->payment_status !== 'Paid'
                && $c->last_payment_date
                && now()->diffInDays($c->last_payment_date, false) < -30;
        });

        $kpis = [
            'total_expected'  => number_format($clients->sum('retainer_fee'), 2),
            'total_overdue'   => number_format($overdue->sum('retainer_fee'), 2),
            'total_collected' => number_format(
                $clients->where('payment_status', 'Paid')->sum('retainer_fee'),
                2
            ),
        ];

        return Inertia::render('Finance/Billing', [
            'kpis'           => $kpis,
            'clientsBilling' => $clients->values(),
        ]);
    }

    public function sendReminder(Request $request, Client $client)
    {
        // Find the client's portal user account and send them the notification
        $clientUser = User::where('client_id', $client->id)->first();

        if ($clientUser) {
            $clientUser->notify(new VatDeadlineReminder($client));
        }

        return back()->with('success', "Reminder sent to {$client->company_name}.");
    }
}
