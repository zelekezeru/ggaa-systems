<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class TeamProject extends Model
{
    protected $guarded = [];

    protected $casts = [
        'start_date'   => 'date',
        'due_date'     => 'date',
        'completed_at' => 'datetime',
    ];

    public const ACTIVE_STATUSES = ['planning', 'in_progress', 'in_review'];
    public const TERMINAL_STATUSES = ['completed', 'cancelled'];

    protected static function booted(): void
    {
        static::addGlobalScope('role_based_access', function (Builder $builder) {
            if (! Auth::check()) {
                return;
            }

            /** @var \App\Models\User $user */
            $user = Auth::user();

            if ($user->hasRole('Super Admin')) {
                return;
            }

            if ($user->hasRole('Branch Manager')) {
                $builder->where('branch_id', $user->branch_id);
                return;
            }

            if ($user->hasRole('Client')) {
                $builder->where('client_id', $user->client_id);
                return;
            }

            // Staff (Employee / Finance Admin / etc.) see projects they belong to.
            $builder->whereHas('members', fn ($q) => $q->where('user_id', $user->id));
        });
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function teamLeader()
    {
        return $this->belongsTo(User::class, 'team_leader_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function members()
    {
        return $this->hasMany(TeamProjectMember::class);
    }

    public function activeMembers()
    {
        return $this->hasMany(TeamProjectMember::class)->whereNull('left_at');
    }

    public function todos()
    {
        return $this->hasMany(TeamProjectTodo::class)->orderBy('order_index');
    }

    public function files()
    {
        return $this->hasMany(TeamProjectFile::class)->latest();
    }

    public function messages()
    {
        return $this->hasMany(TeamProjectMessage::class)->oldest();
    }

    public function isActive(): bool
    {
        return in_array($this->status, self::ACTIVE_STATUSES, true);
    }

    public function getProgressPercentAttribute(): int
    {
        $total = $this->todos()->count();
        if ($total === 0) {
            return $this->status === 'completed' ? 100 : 0;
        }
        $done = $this->todos()->where('status', 'done')->count();
        return (int) round(($done / $total) * 100);
    }

    protected $appends = ['progress_percent'];
}
