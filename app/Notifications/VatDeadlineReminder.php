<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use App\Models\Task;

class VatDeadlineReminder extends Notification
{
    use Queueable;

    public $task;

    public function __construct(Task $task)
    {
        $this->task = $task;
    }

    // Determine HOW to send this. We will send an Email and an in-app Database alert.
    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    // 1. The Email Format
    public function toMail($notifiable)
    {
        return (new MailMessage)
                    ->subject('URGENT: VAT Document Required')
                    ->greeting('Hello ' . ($notifiable->company_name ?? $notifiable->name) . ',')
                    ->line('Your ' . $this->task->template->name . ' is due on ' . $this->task->due_date->format('M d, Y') . '.')
                    ->line('Please upload your receipts to avoid ERCA penalties.')
                    ->action('Go to Client Portal', url('/portal/tasks/' . $this->task->id))
                    ->line('Thank you for choosing GGAA.');
    }

    // 2. The In-App / Database Format (This feeds your Vue.js bell icon)
    public function toArray($notifiable)
    {
        return [
            'task_id' => $this->task->id,
            'title' => 'VAT Deadline Approaching',
            'message' => 'Upload documents by ' . $this->task->due_date->format('M d'),
            'type' => 'warning', // Used for color coding in Vue
        ];
    }
}
