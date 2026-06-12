<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeamProjectMessage extends Model
{
    protected $guarded = [];

    protected $appends = ['attachment_url'];

    public function project()
    {
        return $this->belongsTo(TeamProject::class, 'team_project_id');
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function getAttachmentUrlAttribute(): ?string
    {
        // Served through an auth-checked route (the file lives on the private
        // disk), never a public /storage URL.
        return $this->attachment_path
            ? route('team-projects.messages.attachment', [$this->team_project_id, $this->id])
            : null;
    }
}
