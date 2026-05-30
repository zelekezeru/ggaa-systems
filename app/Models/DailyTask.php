<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class DailyTask extends Model
{
    protected $guarded = [];

    protected $casts = [
        'scheduled_date' => 'date',
        'completed_at'   => 'datetime',
    ];

    public function assignedBy()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    protected static function booted(): void
    {
        static::addGlobalScope('role_based_access', function (Builder $builder) {
            if (! Auth::check()) {
                return;
            }

            /** @var User $user */
            $user = Auth::user();

            if ($user->hasAnyRole(['Super Admin', 'Operation Manager'])) {
                return;
            }

            if ($user->hasRole('Branch Manager')) {
                $builder->where('branch_id', $user->branch_id);
            } elseif ($user->hasRole('Employee')) {
                $builder->where('assigned_to', $user->id);
            } else {
                $builder->whereRaw('0 = 1');
            }
        });
    }
}
