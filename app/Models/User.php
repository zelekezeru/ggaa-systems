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
        ];
    }

    public function getProfilePhotoUrlAttribute()
    {
        return $this->profile_photo_path
            ? asset('storage/' . $this->profile_photo_path)
            : null;
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

    // 1. Calculate Current Workload
    public function getCurrentCapacityLoad()
    {
        // Sum the complexity scores of all assigned clients
        return $this->clients()->sum('complexity_score'); 
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
        $maxCapacity = 30; // Global system setting
        
        return $query->role('Employee')->get()->filter(function ($user) use ($maxCapacity, $requiredPoints) {
            return ($user->getCurrentCapacityLoad() + $requiredPoints) <= $maxCapacity;
        });
    }
}
