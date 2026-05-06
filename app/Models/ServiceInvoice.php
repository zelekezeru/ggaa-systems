<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class ServiceInvoice extends Model
{
    protected $guarded = [];

    protected $casts = [
        'period_start'      => 'date',
        'period_end'        => 'date',
        'due_date'          => 'date',
        'issued_at'         => 'datetime',
        'paid_at'           => 'datetime',
        'services_snapshot' => 'array',
    ];

    protected $appends = ['paid_amount', 'balance_due'];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function payments()
    {
        return $this->hasMany(ServiceInvoicePayment::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getPaidAmountAttribute(): float
    {
        return (float) $this->payments()->where('status', 'Completed')->sum('amount');
    }

    public function getBalanceDueAttribute(): float
    {
        return max(0, (float) $this->amount - $this->paid_amount);
    }

    public function recomputeStatus(): void
    {
        $paid = $this->paid_amount;
        if ($paid <= 0) {
            $status = $this->status === 'cancelled' ? 'cancelled' : ($this->issued_at ? 'sent' : 'draft');
        } elseif ($paid >= (float) $this->amount) {
            $status = 'paid';
            $this->paid_at = now();
        } else {
            $status = 'partially_paid';
        }
        $this->status = $status;
        $this->save();
    }

    public static function generateInvoiceNumber(): string
    {
        $year   = now()->format('Y');
        $count  = static::whereYear('created_at', $year)->count() + 1;
        return sprintf('INV-%s-%04d', $year, $count);
    }

    protected static function booted(): void
    {
        static::addGlobalScope('role_based_access', function (Builder $builder) {
            if (! Auth::check()) {
                return;
            }

            $user = Auth::user();

            if ($user->hasAnyRole(['Super Admin', 'Finance Admin'])) {
                return;
            }

            if ($user->hasRole('Branch Manager')) {
                $builder->whereHas('client', fn ($q) => $q->where('branch_id', $user->branch_id));
                return;
            }

            if ($user->hasRole('Client')) {
                $builder->where('client_id', $user->client_id);
                return;
            }

            $builder->whereRaw('0 = 1');
        });
    }
}
