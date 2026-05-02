<?php

namespace App\Notifications;

use App\Models\Achievement;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AchievementUnlockedNotification extends Notification
{
    use Queueable;

    public function __construct(public Achievement $achievement)
    {
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        return [
            'type'           => 'achievement',
            'title'          => "🎉 Achievement unlocked: {$this->achievement->name}",
            'body'           => $this->achievement->description . " (+{$this->achievement->points} pts)",
            'achievement_id' => $this->achievement->id,
            'icon'           => $this->achievement->icon,
            'tier'           => $this->achievement->tier,
            'url'            => '/performance',
        ];
    }
}
