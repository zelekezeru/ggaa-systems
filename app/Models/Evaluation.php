<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Evaluation extends Model
{
    // Physical table is `staff_evaluations` (the name `evaluations` is reserved
    // by an orphaned InnoDB FK entry on this database).
    protected $table = 'staff_evaluations';

    protected $guarded = [];

    protected $casts = [
        'finalized_at'  => 'datetime',
        'overall_score' => 'decimal:2',
        'total_weight'  => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function evaluator()
    {
        return $this->belongsTo(User::class, 'evaluator_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function scores()
    {
        return $this->hasMany(EvaluationScore::class)->orderBy('id');
    }

    public function isFinalized(): bool
    {
        return $this->status === 'finalized';
    }

    public function getPeriodLabelAttribute(): string
    {
        $months = [1 => 'January', 'February', 'March', 'April', 'May', 'June',
            'July', 'August', 'September', 'October', 'November', 'December'];

        return ($months[$this->period_month] ?? $this->period_month) . ' ' . $this->period_year;
    }

    protected $appends = ['period_label'];

    /**
     * Role-based access mirrors Task/Client: GM & Operation Manager see all,
     * Branch Managers see their branch, staff see only their own evaluations.
     */
    protected static function booted(): void
    {
        static::addGlobalScope('role_based_access', function (Builder $builder) {
            if (! Auth::check()) {
                return;
            }

            /** @var \App\Models\User $user */
            $user = Auth::user();

            if ($user->hasAnyRole(['Super Admin', 'Operation Manager'])) {
                return;
            }

            if ($user->hasRole('Branch Manager')) {
                $builder->where(function ($q) use ($user) {
                    $q->where('branch_id', $user->branch_id)
                      ->orWhere('user_id', $user->id);
                });
                return;
            }

            // Everyone else: only their own evaluations.
            $builder->where('user_id', $user->id);
        });
    }
}
