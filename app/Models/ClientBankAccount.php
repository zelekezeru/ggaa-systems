<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClientBankAccount extends Model
{
    protected $fillable = [
        'client_id',
        'bank_name',
        'account_type',
        'account_number',
        'balance',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}
