<?php

namespace App\Notifications;

use App\Models\Message;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class NewMessageNotification extends Notification
{
    use Queueable;

    public function __construct(public Message $message, public User $sender)
    {
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        $isFromClient = $this->sender->hasRole('Client');

        return [
            'type'        => 'NewMessage',
            'message'     => $isFromClient ? 'New message from client' : 'New message from staff',
            'body'        => sprintf(
                '%s · "%s"',
                $this->message->client->company_name ?? 'Message',
                Str::limit($this->message->body, 80)
            ),
            'client_name' => $this->message->client->company_name,
            'message_id'  => $this->message->id,
            'sender_id'   => $this->sender->id,
            'client_id'   => $this->message->client_id,
            'icon'        => '💬',
            'url'         => $isFromClient ? route('employee.client.messages', $this->message->client_id) : route('client.messages.index'),
        ];
    }
}