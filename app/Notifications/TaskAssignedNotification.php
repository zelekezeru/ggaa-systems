<?php

namespace App\Notifications;

use App\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TaskAssignedNotification extends Notification
{
    use Queueable;

    public function __construct(public Task $task)
    {
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        return [
            'type'        => 'task_assigned',
            'title'       => 'New task assigned',
            'body'        => sprintf(
                'You have been assigned %s for %s. Due %s.',
                $this->task->template->name ?? 'a task',
                $this->task->client->company_name ?? 'a client',
                optional($this->task->due_date)->format('M j, Y') ?? 'soon'
            ),
            'task_id'     => $this->task->id,
            'client_id'   => $this->task->client_id,
            'icon'        => '📋',
            'url'         => '/workspace',
        ];
    }
}
