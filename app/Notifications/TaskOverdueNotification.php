<?php

namespace App\Notifications;

use App\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TaskOverdueNotification extends Notification
{
    use Queueable;

    public function __construct(public Task $task, public int $daysOverdue)
    {
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        return [
            'type'         => 'task_overdue',
            'title'        => $this->daysOverdue === 1 ? 'Task is 1 day overdue' : "Task is {$this->daysOverdue} days overdue",
            'body'         => sprintf(
                '%s for %s is past due. Risk level: %s',
                $this->task->template->name ?? 'A task',
                $this->task->client->company_name ?? 'a client',
                $this->task->risk_level
            ),
            'task_id'      => $this->task->id,
            'client_id'    => $this->task->client_id,
            'days_overdue' => $this->daysOverdue,
            'icon'         => '⚠️',
            'url'          => '/workspace',
        ];
    }
}
