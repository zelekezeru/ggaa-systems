<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class Task extends Model
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

    // Treat these dates as Carbon instances so we can do math on them (like calculating if they are late)
    protected $casts = [
        'due_date'      => 'datetime',
        'completed_at'  => 'datetime',
        'document_path' => 'array',
    ];

    protected $appends = ['risk_level'];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function template()
    {
        return $this->belongsTo(TaskTemplate::class, 'task_template_id');
    }

    public function assignedEmployee()
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function getRiskLevelAttribute(): string
    {
        return $this->calculateRiskLevel();
    }

    public function calculateRiskLevel()
    {
        if ($this->status === 'Done') return '🟢';

        if (! $this->due_date) return '🟢';

        $daysOverdue = now()->diffInDays($this->due_date, false);
        
        // diffInDays with false returns negative if it's in the past (overdue)
        if ($daysOverdue >= 0) return '🟢'; 

        $multiplier = $this->client?->complexity_score ?? 1;
        $weightedDelay = abs($daysOverdue) * $multiplier;

        if ($weightedDelay > 10) return '🔴'; // Level 5 late 2 days = 10, Level 2 late 5 days = 10
        if ($weightedDelay > 5)  return '🟡';
        
        return '🟢';
    }
}
