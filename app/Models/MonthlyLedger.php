<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class MonthlyLedger extends Model
{
    protected $guarded = [];

    protected $casts = [
        'submitted_at' => 'datetime',
        'verified_at'  => 'datetime',
    ];

    protected $appends = [
        'total_sales',
        'available_for_sales',
        'cost_of_goods_sold',
        'gross_profit',
        'total_expenses',
        'net_profit',
        'profit_tax',
        'total_bank_balance',
    ];

    // ── Computed Attributes (mirror the spreadsheet formulas) ──

    public function getTotalSalesAttribute(): float
    {
        return (float) $this->cash_machine_sales + (float) $this->manual_sales;
    }

    public function getAvailableForSalesAttribute(): float
    {
        return (float) $this->beginning_inventory + (float) $this->purchases;
    }

    public function getCostOfGoodsSoldAttribute(): float
    {
        return max(0, $this->available_for_sales - (float) $this->ending_inventory);
    }

    public function getGrossProfitAttribute(): float
    {
        return $this->total_sales - $this->cost_of_goods_sold;
    }

    public function getTotalExpensesAttribute(): float
    {
        return (float) $this->salary_expense
            + (float) $this->pension_expense
            + (float) $this->printing_expense
            + (float) $this->shed_rent
            + (float) $this->stationery_expense
            + (float) $this->office_rent_expense
            + (float) $this->transport_expense
            + (float) $this->machine_fa_expense
            + (float) $this->eeu_expense
            + (float) $this->maintenance_expense
            + (float) $this->advertising_expense
            + (float) $this->uniform_expense
            + (float) $this->indirect_materials_expense
            + (float) $this->depreciation_expense
            + (float) $this->legal_fee_expense
            + (float) $this->bank_interest_expense
            + (float) $this->bank_service_charge;
    }

    public function getNetProfitAttribute(): float
    {
        return $this->gross_profit - $this->total_expenses;
    }

    // Ethiopian business income tax: NIBT × 35% − 24,600
    public function getProfitTaxAttribute(): float
    {
        $nibt = $this->net_profit;
        if ($nibt <= 0) return 0;
        return max(0, ($nibt * 0.35) - 24600);
    }

    public function getTotalBankBalanceAttribute(): float
    {
        return (float) $this->bankAccountBalances()->sum('balance');
    }

    // ── Relationships ──

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function teamProject()
    {
        return $this->belongsTo(TeamProject::class);
    }

    public function submittedBy()
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function verifiedBy()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function bankAccountBalances()
    {
        return $this->hasMany(BankAccountBalance::class);
    }

    // ── Role-Based Global Scope (same pattern as Client and Task) ──

    protected static function booted()
    {
        static::addGlobalScope('role_based_access', function (Builder $builder) {
            if (! Auth::check()) {
                return;
            }

            $user = Auth::user();

            if ($user->hasRole('Super Admin')) {
                return;
            }

            if ($user->hasRole('Branch Manager')) {
                $builder->whereHas('client', fn ($q) => $q->where('branch_id', $user->branch_id));
                return;
            }

            // Finance Admin: read-only visibility into all ledgers (for progress + invoicing context)
            if ($user->hasRole('Finance Admin')) {
                return;
            }

            // Employee / Team Leader: assigned client OR member of the linked team project
            if ($user->hasAnyRole(['Employee', 'Team Leader'])) {
                $builder->where(function ($q) use ($user) {
                    $q->whereHas('client', fn ($c) => $c->where('assigned_employee_id', $user->id))
                      ->orWhereHas('teamProject.members', fn ($m) => $m->where('user_id', $user->id)->whereNull('left_at'));
                });
                return;
            }

            // Client: only their own client's verified entries
            if ($user->hasRole('Client')) {
                $builder->where('client_id', $user->client_id)->where('status', 'verified');
                return;
            }

            $builder->whereRaw('0 = 1');
        });
    }

    // ── Helpers ──

    public static function ethiopianMonths(): array
    {
        return [
            'Meskeram', 'Tiqimt', 'Hidar', 'Tahisas',
            'Tirr', 'Yeketit', 'Megabit', 'Miyaziya',
            'Ginbot', 'Sene', 'Hamle', 'Nehase', 'Pagume',
        ];
    }
}
