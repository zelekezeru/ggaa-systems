<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class PurchaseReceipt extends Model
{
    protected $guarded = [];

    protected $casts = [
        'receipt_date'    => 'date',
        'has_vat_receipt' => 'boolean',
        'amount_before_vat' => 'decimal:2',
        'vat_amount'        => 'decimal:2',
    ];

    protected $appends = ['image_url', 'total_amount'];

    public function getImageUrlAttribute(): ?string
    {
        return $this->image_path ? asset('storage/' . $this->image_path) : null;
    }

    public function getTotalAmountAttribute(): float
    {
        return (float) $this->amount_before_vat + (float) $this->vat_amount;
    }

    // Map expense_category back to the MonthlyLedger column it feeds
    public static function categoryToLedgerField(string $category): ?string
    {
        return match ($category) {
            'raw_material'        => 'purchases',         // feeds beginning_inventory / purchases
            'salary'              => 'salary_expense',
            'pension'             => 'pension_expense',
            'printing'            => 'printing_expense',
            'shed_rent'           => 'shed_rent',
            'stationery'          => 'stationery_expense',
            'office_rent'         => 'office_rent_expense',
            'transport'           => 'transport_expense',
            'machine_fa'          => 'machine_fa_expense',
            'eeu'                 => 'eeu_expense',
            'maintenance'         => 'maintenance_expense',
            'advertising'         => 'advertising_expense',
            'uniform'             => 'uniform_expense',
            'indirect_materials'  => 'indirect_materials_expense',
            'depreciation'        => 'depreciation_expense',
            'legal_fee'           => 'legal_fee_expense',
            'bank_interest'       => 'bank_interest_expense',
            'bank_service_charge' => 'bank_service_charge',
            default               => null,
        };
    }

    // Human-readable category labels
    public static function categoryLabels(): array
    {
        return [
            'raw_material'        => 'Raw Material / Inventory',
            'detergent'           => 'Detergent / Cleaning Supplies',
            'stationery'          => 'Stationery',
            'advertising'         => 'Advertising',
            'maintenance'         => 'Maintenance',
            'telephone'           => 'Telephone / Internet',
            'salary'              => 'Salary',
            'pension'             => 'Pension (11%)',
            'transport'           => 'Transportation',
            'office_rent'         => 'Office Rent',
            'shed_rent'           => 'Shed Rent',
            'printing'            => 'Printing',
            'machine_fa'          => 'Machine / Fixed Asset',
            'eeu'                 => 'EEU (Electricity)',
            'uniform'             => 'Employee Uniform',
            'indirect_materials'  => 'Indirect Materials',
            'depreciation'        => 'Depreciation',
            'legal_fee'           => 'Legal Fee',
            'bank_interest'       => 'Bank Interest',
            'bank_service_charge' => 'Bank Service Charge',
            'other'               => 'Other',
        ];
    }

    // ── Relationships ──

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function capturedBy()
    {
        return $this->belongsTo(User::class, 'captured_by');
    }

    // ── Role-based scope ──

    protected static function booted(): void
    {
        static::addGlobalScope('role_based_access', function (Builder $builder) {
            if (! Auth::check()) return;

            $user = Auth::user();

            if ($user->hasAnyRole(['Super Admin', 'Operation Manager', 'Finance Admin'])) return;

            if ($user->hasRole('Branch Manager')) {
                $builder->whereHas('client', fn ($q) => $q->where('branch_id', $user->branch_id));
                return;
            }

            if ($user->hasAnyRole(['Employee'])) {
                $builder->whereHas('client', fn ($q) => $q->where('assigned_employee_id', $user->id));
                return;
            }

            $builder->whereRaw('0 = 1');
        });
    }
}
