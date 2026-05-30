<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StaffEvaluationMetric extends Model
{
    protected $guarded = [];

    protected $casts = [
        'weight'    => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function metric()
    {
        return $this->belongsTo(EvaluationMetric::class, 'evaluation_metric_id');
    }
}
