<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaskComment extends Model
{
    protected $guarded = [];

    protected $appends = ['attachment_url'];

    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getAttachmentUrlAttribute(): ?string
    {
        // Served through an auth-checked route (the file lives on the private
        // disk), never a public /storage URL.
        return $this->attachment_path
            ? route('tasks.comments.attachment', [$this->task_id, $this->id])
            : null;
    }
}
