<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Task;
use App\Notifications\VatDeadlineReminder;
use Carbon\Carbon;

class CheckUpcomingDeadlines extends Command
{
    protected $signature = 'tasks:check-deadlines';
    protected $description = 'Scans for pending tasks nearing their deadline and notifies clients.';

    public function handle()
    {
        // Find tasks that are exact 3 days away from being due, and are still "Pending"
        $targetDate = Carbon::today()->addDays(3);

        $urgentTasks = Task::where('status', 'Pending')
            ->whereDate('due_date', $targetDate)
            ->with('client') // Eager load the client to prevent N+1 queries
            ->get();

        foreach ($urgentTasks as $task) {
            // Find the User account associated with this Client to send the notification
            $clientUser = \App\Models\User::where('client_id', $task->client_id)->first();
            
            if ($clientUser) {
                // Trigger the email and database alert!
                $clientUser->notify(new VatDeadlineReminder($task));
                
                // Optional: Notify the assigned employee too!
                if ($task->assignedEmployee) {
                    $task->assignedEmployee->notify(new VatDeadlineReminder($task));
                }
            }
        }

        $this->info('Deadline scan complete. Notified ' . $urgentTasks->count() . ' clients.');
    }
}
