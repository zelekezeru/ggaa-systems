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
        // Layered access: Clients see only their tasks; Employees see only their assignments;
        // Branch Managers see all tasks within their branch; Super Admins see everything.
        static::addGlobalScope('role_based_access', function (Builder $builder) {
            if (! Auth::check()) {
                return;
            }

            /** @var \App\Models\User $user */
            $user = Auth::user();

            if ($user->hasRole('Super Admin')) {
                return;
            }

            if ($user->hasRole('Client')) {
                $builder->where('client_id', $user->client_id);
                return;
            }

            if ($user->hasRole('Branch Manager')) {
                $builder->whereHas('client', fn ($q) => $q->where('branch_id', $user->branch_id));
                return;
            }

            if ($user->hasRole('Employee')) {
                $builder->where('assigned_user_id', $user->id);
                return;
            }

            // Fail-closed for unknown / unassigned roles
            $builder->whereRaw('0 = 1');
        });
    }

    public function comments()
    {
        return $this->hasMany(TaskComment::class)->latest();
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
