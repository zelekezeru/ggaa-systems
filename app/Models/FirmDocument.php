<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Carbon\Carbon;

class FirmDocument extends Model
{
    protected $guarded = [];

    protected $casts = [
        'received_at'            => 'datetime',
        'placed_at'              => 'datetime',
        'retrieved_at'           => 'datetime',
        'delay_charge_starts_at' => 'datetime',
        'charge_per_day'         => 'decimal:2',
        'image_paths'            => 'array',
    ];

    protected $appends = [
        'duration_of_stay',
        'delay_days',
        'accumulated_charge',
    ];

    protected static function booted()
    {
        static::creating(function ($document) {
            // Generate DOC-YYYYMM-XXXX format
            if (empty($document->unique_document_id)) {
                $count = self::whereYear('created_at', now()->year)
                    ->whereMonth('created_at', now()->month)
                    ->count() + 1;
                $document->unique_document_id = 'DOC-' . now()->format('Ym') . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
            }

            if (empty($document->qr_code)) {
                $document->qr_code = 'QD-' . strtoupper(Str::random(12));
            }

            if (empty($document->received_at)) {
                $document->received_at = now();
            }

            // Set delay charge start date based on grace period if not explicitly set
            if (empty($document->delay_charge_starts_at)) {
                $document->delay_charge_starts_at = Carbon::parse($document->received_at)->addDays((int) ($document->grace_days ?? 30));
            }
        });

        static::saving(function ($document) {
            if ($document->isDirty('received_at') || $document->isDirty('grace_days')) {
                $document->delay_charge_starts_at = Carbon::parse($document->received_at)->addDays((int) ($document->grace_days ?? 30));
            }
        });
    }

    public function documentType()
    {
        return $this->belongsTo(DocumentType::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function shelfSection()
    {
        return $this->belongsTo(ShelfSection::class);
    }

    /**
     * Total duration of stay in days.
     */
    public function getDurationOfStayAttribute(): int
    {
        $end = $this->retrieved_at ?? now();
        return max(0, Carbon::parse($this->received_at)->diffInDays($end));
    }

    /**
     * Delay days beyond the grace period.
     */
    public function getDelayDaysAttribute(): int
    {
        $start = $this->delay_charge_starts_at;
        if (!$start) {
            return 0;
        }

        $end = $this->retrieved_at ?? now();
        if ($end->lt($start)) {
            return 0;
        }

        return max(0, $start->diffInDays($end));
    }

    /**
     * Accumulated delay storage fee.
     */
    public function getAccumulatedChargeAttribute(): float
    {
        return (float) ($this->delay_days * ($this->charge_per_day ?? 50.00));
    }
}
