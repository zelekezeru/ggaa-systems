<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeamProjectMember extends Model
{
    protected $guarded = [];

    protected $casts = [
        'joined_at' => 'datetime',
        'left_at'   => 'datetime',
    ];

    public function project()
    {
        return $this->belongsTo(TeamProject::class, 'team_project_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function isLeader(): bool
    {
        return $this->role_in_team === 'leader';
    }
}
