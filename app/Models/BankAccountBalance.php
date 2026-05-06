<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BankAccountBalance extends Model
{
    protected $guarded = [];

    public function bankAccount()
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function monthlyLedger()
    {
        return $this->belongsTo(MonthlyLedger::class);
    }
}
