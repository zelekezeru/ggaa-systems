<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Announcement extends Model
{
    protected $guarded = [];

    protected $casts = [
        'recipients'      => 'array',
        'recipient_count' => 'integer',
        'sent_count'      => 'integer',
        'failed_count'    => 'integer',
        'sent_at'         => 'datetime',
    ];

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
}
