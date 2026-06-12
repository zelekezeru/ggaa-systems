<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
        // Files live on the private disk — link to the auth-checked download
        // route rather than a public /storage URL.
        return $this->path
            ? route('team-projects.files.download', [$this->team_project_id, $this->id])
            : null;
    }
}
