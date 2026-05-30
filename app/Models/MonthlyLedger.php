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
        'cash_machine_start_number'   => 'string',
        'cash_machine_end_number'     => 'string',
        'manual_receipt_start_number' => 'string',
        'manual_receipt_end_number'   => 'string',
        'inventory_items_start'       => 'decimal:3',
        'inventory_items_end'         => 'decimal:3',
        'inventory_sold_quantity'     => 'decimal:3',
        'custom_expenses'             => 'array',
        'hidden_expense_fields'       => 'array',
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
        'cash_machine_sales_count',
        'manual_receipt_count',
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
        $hidden = $this->hidden_expense_fields ?? [];

        $standard = [
            'salary_expense', 'pension_expense', 'printing_expense', 'shed_rent',
            'stationery_expense', 'office_rent_expense', 'transport_expense',
            'machine_fa_expense', 'eeu_expense', 'maintenance_expense',
            'advertising_expense', 'uniform_expense', 'indirect_materials_expense',
            'depreciation_expense', 'legal_fee_expense', 'bank_interest_expense',
            'bank_service_charge',
        ];

        $total = 0.0;
        foreach ($standard as $field) {
            if (! in_array($field, $hidden, true)) {
                $total += (float) $this->$field;
            }
        }

        foreach ($this->custom_expenses ?? [] as $extra) {
            $total += (float) ($extra['amount'] ?? 0);
        }

        return $total;
    }

    public function getNetProfitAttribute(): float
    {
        return $this->gross_profit - $this->total_expenses;
    }

    // Ethiopian business income tax: NIBT × rate% − 24,600 (top-bracket deduction, default 35%)
    public function getProfitTaxAttribute(): float
    {
        $nibt = $this->net_profit;
        if ($nibt <= 0) return 0;
        $rate = (float) ($this->tax_rate ?? 35);
        return max(0, ($nibt * $rate / 100) - 24600);
    }

    public function getTotalBankBalanceAttribute(): float
    {
        return (float) $this->bankAccountBalances()->sum('balance');
    }

    public function getCashMachineSalesCountAttribute(): int
    {
        $start = $this->cash_machine_start_number;
        $end   = $this->cash_machine_end_number;
        if ($start === null || $end === null || $end < $start) return 0;
        return (int) ($end - $start);
    }

    public function getManualReceiptCountAttribute(): int
    {
        $start = $this->manual_receipt_start_number;
        $end   = $this->manual_receipt_end_number;
        if ($start === null || $end === null || $end < $start) return 0;
        return (int) ($end - $start);
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

            if ($user->hasAnyRole(['Super Admin', 'Operation Manager'])) {
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
