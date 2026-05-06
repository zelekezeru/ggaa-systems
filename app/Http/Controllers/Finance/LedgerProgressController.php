<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\MonthlyLedger;
use Inertia\Inertia;

class LedgerProgressController extends Controller
{
    public function index()
    {
        $clients = Client::withoutGlobalScopes()
            ->select(['id', 'company_name', 'tin_number'])
            ->orderBy('company_name')
            ->get();

        $ledgers = MonthlyLedger::select(['id', 'client_id', 'eth_year', 'eth_month', 'status'])
            ->get()
            ->groupBy('client_id');

        $rows = $clients->map(function ($c) use ($ledgers) {
            $entries = ($ledgers[$c->id] ?? collect())
                ->keyBy(fn ($l) => $l->eth_year . '_' . $l->eth_month)
                ->map(fn ($l) => $l->only(['id', 'eth_year', 'eth_month', 'status']));
            return [
                'id'           => $c->id,
                'company_name' => $c->company_name,
                'tin_number'   => $c->tin_number,
                'entries'      => $entries,
            ];
        });

        return Inertia::render('Finance/LedgerProgress', [
            'clients'         => $rows,
            'ethiopianMonths' => MonthlyLedger::ethiopianMonths(),
            'currentEthYear'  => $this->currentEthYear(),
        ]);
    }

    private function currentEthYear(): int
    {
        $now = now();
        $eth = $now->year - 7;
        if ($now->month < 9 || ($now->month === 9 && $now->day < 11)) {
            $eth--;
        }
        return $eth;
    }
}
