<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeamProjectTodo extends Model
{
    protected $guarded = [];

    protected $casts = [
        'due_date'     => 'date',
        'completed_at' => 'datetime',
    ];

    public function project()
    {
        return $this->belongsTo(TeamProject::class, 'team_project_id');
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
