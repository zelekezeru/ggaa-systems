<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\MonthlyLedger;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class LedgerController extends Controller
{
    public function index()
    {
        // Global scope already filters to: own client + status=verified
        $ledgers = MonthlyLedger::with(['verifiedBy:id,name', 'submittedBy:id,name'])
            ->orderByDesc('eth_year')
            ->get()
            ->map(fn ($l) => [
                'id'           => $l->id,
                'eth_year'     => $l->eth_year,
                'eth_month'    => $l->eth_month,
                'verified_at'  => $l->verified_at,
                'verified_by'  => $l->verifiedBy?->name,
                'total_sales'  => $l->total_sales,
                'gross_profit' => $l->gross_profit,
                'net_profit'   => $l->net_profit,
                'profit_tax'   => $l->profit_tax,
            ]);

        $byYear = $ledgers->groupBy('eth_year');

        return Inertia::render('Client/FinancialLedger', [
            'ledgersByYear'    => $byYear,
            'ethiopianMonths'  => MonthlyLedger::ethiopianMonths(),
        ]);
    }
}
