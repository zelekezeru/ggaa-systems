<?php

namespace App\Notifications;

use App\Models\DailyTask;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class DailyTaskAssignedNotification extends Notification
{
    use Queueable;

    public function __construct(public readonly DailyTask $task) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'daily_task_id'  => $this->task->id,
            'title'          => $this->task->title,
            'type'           => $this->task->type,
            'priority'       => $this->task->priority,
            'scheduled_date' => $this->task->scheduled_date->toDateString(),
            'scheduled_time' => $this->task->scheduled_time,
            'assigned_by'    => $this->task->assignedBy->name,
        ];
    }
}
