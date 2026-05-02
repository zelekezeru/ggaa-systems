<?php

namespace App\Notifications;

use App\Models\TeamProject;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TeamProjectStatusChangedNotification extends Notification
{
    use Queueable;

    public function __construct(public TeamProject $project, public string $newStatus)
    {
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        $icon = match ($this->newStatus) {
            'completed'   => '✅',
            'cancelled'   => '🛑',
            'in_progress' => '🚀',
            'in_review'   => '👀',
            default       => '🔄',
        };

        return [
            'type'  => 'team_project_status_changed',
            'title' => 'Team project status updated',
            'body'  => sprintf(
                '"%s" is now %s.',
                $this->project->title,
                str_replace('_', ' ', $this->newStatus),
            ),
            'project_id' => $this->project->id,
            'icon'       => $icon,
            'url'        => '/team-projects/' . $this->project->id,
        ];
    }
}
