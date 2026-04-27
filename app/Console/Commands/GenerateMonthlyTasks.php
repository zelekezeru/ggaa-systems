<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Client;
use App\Models\TaskTemplate;
use App\Models\Task;
use Carbon\Carbon;

class GenerateMonthlyTasks extends Command
{
    // The command you can run in terminal: php artisan tasks:generate-monthly
    protected $signature = 'tasks:generate-monthly';
    protected $description = 'Automatically generates monthly tax and accounting tasks for active clients.';

    public function handle()
    {
        $this->info('Starting monthly task generation...');

        // 1. Get all templates that happen monthly (e.g., VAT, Payroll)
        $monthlyTemplates = TaskTemplate::where('frequency', 'Monthly')->get();

        // 2. Get all ACTIVE clients (we don't generate tasks for paused/churned clients)
        // Notice we don't need to worry about branch scopes here because console commands bypass Auth!
        $activeClients = Client::where('status', 'Active')->get();

        $count = 0;
        $currentMonth = Carbon::now()->startOfMonth();

        foreach ($activeClients as $client) {
            foreach ($monthlyTemplates as $template) {
                
                // Calculate the exact due date for this specific month
                $dueDate = Carbon::now()->setDay($template->due_date_offset)->endOfDay();

                // 3. Create the task! 
                // We use firstOrCreate so if the system accidentally runs twice, it won't create duplicate tasks.
                $task = Task::firstOrCreate([
                    'client_id' => $client->id,
                    'task_template_id' => $template->id,
                    'due_date' => $dueDate,
                ], [
                    'assigned_user_id' => $client->assigned_employee_id,
                    'status' => 'Pending',
                    'risk_level' => '🟢', // Starts as OK
                ]);

                if ($task->wasRecentlyCreated) {
                    $count++;
                }
            }
        }

        $this->info("Successfully generated {$count} new tasks for " . Carbon::now()->format('F Y') . "!");
    }
}
