<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class Client extends Model
{
    protected $guarded = [];
    
    protected $appends = [
        'logo_url',
    ];

    public function getLogoUrlAttribute()
    {
        return $this->logo_path
            ? asset('storage/' . $this->logo_path)
            : null;
    }

    // --- Relationships ---
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function serviceTypes()
    {
        return $this->belongsToMany(ServiceType::class);
    }

    public function assignedEmployee()
    {
        return $this->belongsTo(User::class, 'assigned_employee_id');
    }

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    public function user()
    {
        return $this->hasOne(User::class);
    }

    // --- The "Invisible Security" Layer (Global Scopes) ---
    protected static function booted()
    {
        static::addGlobalScope('role_based_access', function (Builder $builder) {
            if (Auth::check()) {
                $user = Auth::user();

                // 1. Super Admin: Highest privilege, sees everything
                if ($user->hasRole('Super Admin')) {
                    return;
                }

                // 2. Branch Manager: Sees all clients in their specific branch
                elseif ($user->hasRole('Branch Manager')) {
                    $builder->where('branch_id', $user->branch_id);
                }

                // 3. Employee: Sees only their specifically assigned clients
                elseif ($user->hasRole('Employee')) {
                    $builder->where('assigned_employee_id', $user->id);
                }

                // 4. Client: Sees only their own client profile
                elseif ($user->hasRole('Client')) {
                    $builder->where('id', $user->client_id);
                }

                // 5. Default Fail-Safe: If unassigned or other role, return zero results
                else {
                    $builder->whereRaw('0 = 1');
                }
            }
        });
    }
}
