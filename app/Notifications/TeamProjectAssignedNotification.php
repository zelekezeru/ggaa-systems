<?php

namespace App\Notifications;

use App\Models\TeamProject;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TeamProjectAssignedNotification extends Notification
{
    use Queueable;

    public function __construct(public TeamProject $project, public string $roleInTeam = 'member')
    {
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        $roleLabel = $this->roleInTeam === 'leader' ? 'team leader' : 'team member';

        return [
            'type'  => 'team_project_assigned',
            'title' => 'Added to a team project',
            'body'  => sprintf(
                'You are %s on "%s". Due %s.',
                $roleLabel,
                $this->project->title,
                optional($this->project->due_date)->format('M j, Y') ?? 'TBD',
            ),
            'project_id' => $this->project->id,
            'icon'       => $this->roleInTeam === 'leader' ? '🎖️' : '🧩',
            'url'        => '/team-projects/' . $this->project->id,
        ];
    }
}
