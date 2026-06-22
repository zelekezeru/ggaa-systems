<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles;

    protected $guarded = [];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $appends = [
        'profile_photo_url',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'must_change_password' => 'boolean',
        ];
    }

    public function getProfilePhotoUrlAttribute()
    {
        return $this->profile_photo_path
            ? asset('storage/' . $this->profile_photo_path)
            : null;
    }

    /**
     * The landing route this user is sent to after login. Every role must map
     * to a route its middleware actually permits — otherwise the post-login
     * redirect throws a 403 ("User does not have the right roles").
     */
    public function homeRoute(): string
    {
        return match (true) {
            $this->hasAnyRole(['Super Admin', 'Operation Manager', 'Branch Manager']) => 'super-admin.dashboard',
            $this->hasRole('Finance Admin') => 'finance.billing',
            $this->hasRole('Client') => 'client.dashboard',
            $this->hasAnyRole(['Employee', 'Team Leader']) => 'employee.workspace',
            default => 'profile.edit',
        };
    }

    // --- Relationships ---
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function serviceTypes()
    {
        return $this->belongsToMany(ServiceType::class, 'user_service_type');
    }

    // A manager manages a branch
    public function managedBranch()
    {
        return $this->hasOne(Branch::class, 'manager_id');
    }

    // An employee has assigned clients
    public function clients()
    {
        return $this->hasMany(Client::class, 'assigned_employee_id');
    }

    public function staffProfile()
    {
        return $this->hasOne(StaffUser::class);
    }

    public function teamProjectMemberships()
    {
        return $this->hasMany(TeamProjectMember::class);
    }

    public function teamProjects()
    {
        return $this->belongsToMany(TeamProject::class, 'team_project_members')
            ->withPivot(['role_in_team', 'complexity_share', 'joined_at', 'left_at'])
            ->withTimestamps();
    }

    public function ledTeamProjects()
    {
        return $this->hasMany(TeamProject::class, 'team_leader_id');
    }

    public function isStaff(): bool
    {
        return $this->staffProfile()->where('is_active', true)->exists();
    }

    // 1. Calculate Current Workload (clients + active team-project shares)
    public function getCurrentCapacityLoad()
    {
        $clientLoad = (int) $this->clients()->sum('complexity_score');

        $teamLoad = (int) $this->teamProjectMemberships()
            ->whereNull('left_at')
            ->whereHas('project', fn ($q) => $q->whereIn('status', TeamProject::ACTIVE_STATUSES))
            ->sum('complexity_share');

        return $clientLoad + $teamLoad;
    }

    // 2. Calculate Weighted Performance (For the Manager Dashboard)
    public function getWeightedPerformanceScore($month, $year)
    {
        // Get all tasks assigned to this employee for the given month
        $tasks = Task::with('client')
            ->where('assigned_user_id', $this->id)
            ->whereMonth('due_date', $month)
            ->whereYear('due_date', $year)
            ->get();

        if ($tasks->isEmpty()) return 100; // No tasks = perfect score

        $totalPossiblePoints = 0;
        $earnedPoints = 0;

        foreach ($tasks as $task) {
            // The value of the task is determined by the client's weight
            // Handle tasks without clients (fallback to 1) or skip if client is deleted
            if (!$task->client) continue;

            $taskWeight = $task->client->complexity_score;
            $totalPossiblePoints += $taskWeight;

            // If completed on time, they get full points
            if ($task->status === 'Done' && $task->completed_at <= $task->due_date) {
                $earnedPoints += $taskWeight;
            } 
            // If completed but late, they get half points
            elseif ($task->status === 'Done' && $task->completed_at > $task->due_date) {
                $earnedPoints += ($taskWeight * 0.5);
            }
        }

        if ($totalPossiblePoints === 0) return 100;

        // Return a percentage
        return round(($earnedPoints / $totalPossiblePoints) * 100, 2);
    }

    /**
     * Scope to find employees who have enough remaining capacity points
     * to take on a client with the specified complexity.
     */
    public function scopeAvailableForComplexity($query, $requiredPoints)
    {
        return $query->role('Employee')->get()->filter(function ($user) use ($requiredPoints) {
            $maxCapacity = $user->staffProfile->max_capacity ?? (int) config('workforce.max_capacity');
            return ($user->getCurrentCapacityLoad() + $requiredPoints) <= $maxCapacity;
        });
    }

    /**
     * Active staff (any role except Client) eligible to be assigned to team projects
     * or tasks. Used by the Team Project flow.
     */
    public function scopeAssignableStaff($query)
    {
        return $query->whereHas('staffProfile', fn ($q) => $q->where('is_active', true));
    }

    public function getCapacityPercentAttribute(): int
    {
        $max = $this->staffProfile->max_capacity ?? (int) config('workforce.max_capacity');
        if ($max <= 0) return 0;
        return (int) min(100, round(($this->getCurrentCapacityLoad() / $max) * 100));
    }

    public function getRemainingCapacityAttribute(): int
    {
        $max = $this->staffProfile->max_capacity ?? (int) config('workforce.max_capacity');
        return max(0, $max - $this->getCurrentCapacityLoad());
    }

    public function achievements()
    {
        return $this->belongsToMany(Achievement::class, 'user_achievements')
            ->withPivot('earned_at')
            ->withTimestamps();
    }
}
