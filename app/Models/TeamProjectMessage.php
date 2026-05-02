<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

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
        return $this->attachment_path ? Storage::disk('public')->url($this->attachment_path) : null;
    }
}
