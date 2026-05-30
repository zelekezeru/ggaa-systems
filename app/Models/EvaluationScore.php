<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EvaluationScore extends Model
{
    protected $guarded = [];

    protected $casts = [
        'weight'           => 'decimal:2',
        'max_score'        => 'decimal:2',
        'raw_score'        => 'decimal:2',
        'normalized_score' => 'decimal:2',
        'weighted_score'   => 'decimal:2',
        'is_auto'          => 'boolean',
        'scored_at'        => 'datetime',
    ];

    public function evaluation()
    {
        return $this->belongsTo(Evaluation::class);
    }

    public function metric()
    {
        return $this->belongsTo(EvaluationMetric::class, 'evaluation_metric_id');
    }

    public function scorer()
    {
        return $this->belongsTo(User::class, 'scored_by');
    }
}
