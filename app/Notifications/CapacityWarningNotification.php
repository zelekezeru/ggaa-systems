<?php

namespace App\Notifications;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class CapacityWarningNotification extends Notification
{
    use Queueable;

    /**
     * Sent to Branch Managers / Super Admins when team utilization crosses
     * the configured warning threshold (default 80%).
     */
    public function __construct(
        public Branch $branch,
        public int $utilizationPct,
        public int $employeesAtCapacity,
        public int $totalEmployees,
    ) {
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        return [
            'type'                 => 'capacity_warning',
            'title'                => "Team capacity at {$this->utilizationPct}%",
            'body'                 => sprintf(
                '%s branch is running hot — %d of %d employees are at or near capacity. Consider rebalancing or hiring.',
                $this->branch->name,
                $this->employeesAtCapacity,
                $this->totalEmployees
            ),
            'branch_id'            => $this->branch->id,
            'utilization_pct'      => $this->utilizationPct,
            'employees_at_cap'     => $this->employeesAtCapacity,
            'total_employees'      => $this->totalEmployees,
            'icon'                 => '🔥',
            'url'                  => '/super-admin/availability',
        ];
    }
}
