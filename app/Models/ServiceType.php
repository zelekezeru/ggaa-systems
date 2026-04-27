<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ServiceType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'complexity_weight',
        'is_active',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($serviceType) {
            if (!$serviceType->slug) {
                $serviceType->slug = Str::slug($serviceType->name);
            }
        });
    }

    public function clients()
    {
        return $this->belongsToMany(Client::class);
    }
}
