<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ClientMessageReceived extends Notification
{
    use Queueable;

    protected $client;
    protected $message;

    /**
     * Create a new notification instance.
     */
    public function __construct($client, $message)
    {
        $this->client = $client;
        $this->message = $message;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type'  => 'Message',
            'title' => 'New Message from ' . $this->client->company_name,
            'body'  => \Illuminate\Support\Str::limit($this->message->body, 50),
            'url'   => route('employee.client.messages', $this->client->id),
            'icon'  => '💬',
        ];
    }
}
