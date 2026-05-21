<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Shelf extends Model
{
    protected $guarded = [];

    protected static function booted()
    {
        static::creating(function ($shelf) {
            if (empty($shelf->qr_code)) {
                $shelf->qr_code = 'SHLF-' . strtoupper(Str::random(10));
            }
        });
    }

    public function sections()
    {
        return $this->hasMany(ShelfSection::class);
    }
}
