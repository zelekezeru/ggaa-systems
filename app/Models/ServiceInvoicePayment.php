<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceInvoicePayment extends Model
{
    protected $guarded = [];

    protected $casts = [
        'paid_at' => 'date',
    ];

    public function invoice()
    {
        return $this->belongsTo(ServiceInvoice::class, 'service_invoice_id');
    }

    public function recordedBy()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    protected static function booted(): void
    {
        static::saved(fn ($p) => $p->invoice?->recomputeStatus());
        static::deleted(fn ($p) => $p->invoice?->recomputeStatus());
    }
}
