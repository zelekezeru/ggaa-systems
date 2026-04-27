<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class Message extends Model
{
    protected $guarded = [];

    protected static function booted()
    {
        static::addGlobalScope('client_privacy', function (Builder $builder) {
            if (Auth::check() && Auth::user()->hasRole('Client')) {
                // Force the query to ONLY return data matching their specific client_id
                $builder->where('client_id', Auth::user()->client_id);
            }
        });
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
}
