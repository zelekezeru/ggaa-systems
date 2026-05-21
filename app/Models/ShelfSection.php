<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ShelfSection extends Model
{
    protected $guarded = [];

    protected static function booted()
    {
        static::creating(function ($section) {
            if (empty($section->qr_code)) {
                $section->qr_code = 'SECT-' . strtoupper(Str::random(12));
            }
        });
    }

    public function shelf()
    {
        return $this->belongsTo(Shelf::class);
    }

    public function documents()
    {
        return $this->hasMany(FirmDocument::class);
    }
}
