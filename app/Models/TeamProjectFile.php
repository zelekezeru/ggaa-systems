<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class TeamProjectFile extends Model
{
    protected $guarded = [];

    protected $appends = ['url'];

    public function project()
    {
        return $this->belongsTo(TeamProject::class, 'team_project_id');
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function getUrlAttribute(): ?string
    {
        return $this->path ? Storage::disk('public')->url($this->path) : null;
    }
}
