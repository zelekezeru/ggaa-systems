<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateTaskStatusRequest;
use App\Models\DailyTask;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class WorkspaceController extends Controller
{
    private const MAX_CAPACITY = 30;

    public function index()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $tasks = Task::with(['client', 'template'])
            ->where('assigned_user_id', $user->id)
            ->orderBy('due_date')
            ->get()
            ->groupBy('status');

        $columns = ['Waiting on Client', 'To Do', 'In Review', 'Done'];

        $grouped = collect($columns)->mapWithKeys(fn ($col) => [
            $col => $tasks->get($col, collect())->values(),
        ]);

        $currentLoad     = $user->getCurrentCapacityLoad();
        $capacityPercent = min(100, round(($currentLoad / self::MAX_CAPACITY) * 100));



        return Inertia::render('Employee/Workspace', [
            'tasksByStatus'   => $grouped,
            'employee'        => $user->only('id', 'name'),
            'currentLoad'     => $currentLoad,
            'maxCapacity'     => self::MAX_CAPACITY,
            'capacityPercent' => $capacityPercent,
        ]);
    }

    public function dailyTasks(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $date = $request->query('date', today()->toDateString());

        $dailyTasks = DailyTask::where('assigned_to', $user->id)
            ->whereDate('scheduled_date', $date)
            ->orderByRaw("FIELD(priority, 'urgent', 'normal')")
            ->orderBy('scheduled_time')
            ->get();

        return Inertia::render('Employee/DailyTasks', [
            'dailyTasks'  => $dailyTasks,
            'currentDate' => $date,
            'employee'    => $user->only('id', 'name'),
        ]);
    }

    public function storeDailyTask(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $validated = $request->validate([
            'title'          => 'required|string|max:255',
            'type'           => 'required|string|in:mail_delivery,client_visit,tax_commission,errand,internal_meeting,other',
            'description'    => 'nullable|string',
            'priority'       => 'nullable|in:urgent,normal',
            'scheduled_time' => 'nullable|date_format:H:i',
            'scheduled_date' => 'nullable|date',
        ]);

        DailyTask::create(array_merge($validated, [
            'assigned_by'    => $user->id,
            'assigned_to'    => $user->id,
            'status'         => 'pending',
            'scheduled_date' => $request->input('scheduled_date', today()->toDateString()),
            'branch_id'      => $user->branch_id,
        ]));

        return back();
    }

    public function updateStatus(UpdateTaskStatusRequest $request, Task $task)
    {
        $data = ['status' => $request->validated('status')];

        if ($request->validated('status') === 'Done') {
            $data['completed_at'] = now();
        }

        $task->update($data);

        return back();
    }

    public function updateDailyTaskStatus(Request $request, DailyTask $dailyTask)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        abort_unless($dailyTask->assigned_to === $user->id, 403);

        $validated = $request->validate([
            'status' => ['required', 'in:pending,in_progress,done,cancelled'],
            'notes'  => ['nullable', 'string', 'max:1000'],
        ]);

        $data = ['status' => $validated['status']];

        if (! empty($validated['notes'])) {
            $data['notes'] = $validated['notes'];
        }

        if ($validated['status'] === 'done') {
            $data['completed_at'] = now();
        }

        $dailyTask->update($data);

        return back()->with('success', 'Task updated.');
    }

    public function updateDetails(Request $request, Task $task)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        abort_unless($task->assigned_user_id === $user->id, 403);

        $request->validate([
            'notes' => ['nullable', 'string', 'max:1000'],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['file', 'max:10240'],
        ]);

        $data = [];

        if ($request->has('notes')) {
            $data['notes'] = $request->notes;
        }

        if ($request->hasFile('attachments')) {
            $directory = 'client_vault/' . ($task->client?->tin_number ?? 'unassigned') . '/' . now()->format('Y-m');
            $existingPaths = is_array($task->document_path) ? $task->document_path : [];
            $newPaths = [];
            foreach ($request->file('attachments') as $file) {
                $newPaths[] = $file->store($directory, 'local');
            }
            $data['document_path'] = array_merge($existingPaths, $newPaths);
        }

        // If they click 'Submit to Review', we can let frontend patch the status to 'In Review' 
        // separately via updateStatus. For here, we just save details.

        $task->update($data);

        return back()->with('success', 'Task details updated successfully.');
    }
    public function performance()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // 1. Calculate Score (current month)
        $score = $user->getWeightedPerformanceScore(now()->month, now()->year);

        // 2. Task Stats (Total lifetime)
        $totalDone = Task::where('assigned_user_id', $user->id)->where('status', 'Done')->count();
        $totalOnTime = Task::where('assigned_user_id', $user->id)
            ->where('status', 'Done')
            ->whereColumn('completed_at', '<=', 'due_date')
            ->count();
        
        $onTimeRate = $totalDone > 0 ? round(($totalOnTime / $totalDone) * 100) : 100;

        // 3. Efficiency (Daily tasks completion)
        $dailyTasksToday = DailyTask::where('assigned_to', $user->id)->whereDate('scheduled_date', today())->count();
        $dailyTasksDone = DailyTask::where('assigned_to', $user->id)->whereDate('scheduled_date', today())->where('status', 'done')->count();
        $efficiency = $dailyTasksToday > 0 ? round(($dailyTasksDone / $dailyTasksToday) * 100) : 100;

        // 4. Activity Pulse (Last 7 days tasks done)
        $pulse = collect(range(6, 0))->map(function($daysAgo) use ($user) {
            $date = today()->subDays($daysAgo);
            return [
                'day'   => $date->format('D'),
                'count' => Task::where('assigned_user_id', $user->id)
                    ->where('status', 'Done')
                    ->whereDate('completed_at', $date)
                    ->count() + 
                    DailyTask::where('assigned_to', $user->id)
                    ->where('status', 'done')
                    ->whereDate('completed_at', $date)
                    ->count()
            ];
        });

        // 5. Workload Heat
        $currentLoad = $user->getCurrentCapacityLoad();
        $healthStatus = $currentLoad > 25 ? 'Overloaded' : ($currentLoad > 15 ? 'Balanced' : 'Growing');

        return Inertia::render('Employee/Performance', [
            'stats' => [
                'score'        => $score,
                'onTimeRate'   => $onTimeRate,
                'efficiency'   => $efficiency,
                'healthStatus' => $healthStatus,
                'totalDone'    => $totalDone,
                'pulse'        => $pulse,
                'load'         => $currentLoad,
            ],
            'employee' => $user->only('id', 'name'),
        ]);
    }
}
