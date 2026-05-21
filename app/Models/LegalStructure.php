<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LegalStructure extends Model
{
    protected $fillable = ['name', 'description'];

    public function clients()
    {
        return $this->hasMany(Client::class);
    }
}
