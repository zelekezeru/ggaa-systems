<?php

namespace App\Notifications;

use App\Models\Task;
use App\Models\TaskComment;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class NewCommentNotification extends Notification
{
    use Queueable;

    public function __construct(public Task $task, public TaskComment $comment, public User $author)
    {
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        return [
            'type'       => 'task_comment',
            'title'      => "{$this->author->name} commented on a task",
            'body'       => sprintf(
                '%s · "%s"',
                $this->task->client->company_name ?? 'Task',
                Str::limit($this->comment->body, 80)
            ),
            'task_id'    => $this->task->id,
            'comment_id' => $this->comment->id,
            'author_id'  => $this->author->id,
            'icon'       => '💬',
            'url'        => '/workspace',
        ];
    }
}
