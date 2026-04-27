<?php

namespace App\Console\Commands;

use App\Models\DailyTask;
use App\Notifications\DailyTaskAssignedNotification;
use Illuminate\Console\Command;

class SendDailyTaskReminders extends Command
{
    protected $signature   = 'daily-tasks:remind {--date= : Date to remind for (Y-m-d, defaults to tomorrow)}';
    protected $description = 'Send reminder notifications for daily tasks scheduled for the given date.';

    public function handle(): int
    {
        $date = $this->option('date')
            ? now()->parse($this->option('date'))
            : now()->addDay();

        $tasks = DailyTask::withoutGlobalScopes()
            ->with(['assignedTo', 'assignedBy'])
            ->whereDate('scheduled_date', $date)
            ->whereIn('status', ['pending'])
            ->get();

        if ($tasks->isEmpty()) {
            $this->info("No pending tasks found for {$date->toDateString()}.");
            return self::SUCCESS;
        }

        foreach ($tasks as $task) {
            $task->assignedTo->notify(new DailyTaskAssignedNotification($task));
        }

        $this->info("Sent {$tasks->count()} reminder(s) for {$date->toDateString()}.");

        return self::SUCCESS;
    }
}
