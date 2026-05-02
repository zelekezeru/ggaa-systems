<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StaffUser extends Model
{
    protected $guarded = [];

    protected $casts = [
        'hire_date' => 'date',
        'is_active' => 'boolean',
    ];

    public const POSITIONS = [
        'employee'    => 'Employee',
        'manager'     => 'Manager',
        'team_leader' => 'Team Leader',
        'admin'       => 'Admin',
        'finance'     => 'Finance',
        'other'       => 'Other',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
