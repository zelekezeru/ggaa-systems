<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateTaskStatusRequest;
use App\Models\DailyTask;
use App\Models\Task;
use App\Services\AchievementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class WorkspaceController extends Controller
{
    public function index()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $maxCapacity = (int) config('workforce.max_capacity');

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
        $capacityPercent = $maxCapacity > 0 ? min(100, (int) round(($currentLoad / $maxCapacity) * 100)) : 0;

        return Inertia::render('Employee/Workspace', [
            'tasksByStatus'   => $grouped,
            'employee'        => $user->only('id', 'name'),
            'currentLoad'     => $currentLoad,
            'maxCapacity'     => $maxCapacity,
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
            ->orderByRaw("CASE priority WHEN 'urgent' THEN 1 WHEN 'normal' THEN 2 ELSE 3 END")
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

    public function updateStatus(UpdateTaskStatusRequest $request, Task $task, AchievementService $achievements)
    {
        $newStatus = $request->validated('status');
        $data = ['status' => $newStatus];

        if ($newStatus === 'Done') {
            $data['completed_at'] = now();
        }

        $task->update($data);

        // Re-evaluate achievements when work is finished — gives the employee
        // immediate recognition for hitting milestones.
        if ($newStatus === 'Done' && $task->assigned_user_id) {
            $achievements->evaluate($task->assignedEmployee()->first());
        }

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

        // 6. Heatmap — last 90 days of completed daily tasks
        $heatmapRaw = DailyTask::where('assigned_to', $user->id)
            ->where('status', 'done')
            ->whereBetween('scheduled_date', [today()->subDays(89)->toDateString(), today()->toDateString()])
            ->selectRaw('DATE(scheduled_date) as date, COUNT(*) as count')
            ->groupBy('date')
            ->pluck('count', 'date');

        $heatmap = collect(range(89, 0))->map(function ($daysAgo) use ($heatmapRaw) {
            $date = today()->subDays($daysAgo)->toDateString();
            return [
                'date'  => $date,
                'count' => (int) ($heatmapRaw[$date] ?? 0),
            ];
        })->values();

        // 7. Achievements (earned + locked, with progress hints)
        $earnedAchievements = $user->achievements()
            ->orderByDesc('user_achievements.earned_at')
            ->get()
            ->map(fn ($a) => [
                'id'          => $a->id,
                'key'         => $a->key,
                'name'        => $a->name,
                'description' => $a->description,
                'icon'        => $a->icon,
                'tier'        => $a->tier,
                'points'      => $a->points,
                'earned_at'   => $a->pivot->earned_at,
            ]);

        $lockedAchievements = \App\Models\Achievement::where('is_active', true)
            ->whereNotIn('id', $earnedAchievements->pluck('id'))
            ->get()
            ->map(fn ($a) => [
                'id'          => $a->id,
                'key'         => $a->key,
                'name'        => $a->name,
                'description' => $a->description,
                'icon'        => $a->icon,
                'tier'        => $a->tier,
                'points'      => $a->points,
            ]);

        // 8. Branch leaderboard — peers by points + monthly performance score
        $leaderboard = \App\Models\User::role('Employee')
            ->where('branch_id', $user->branch_id)
            ->withCount(['achievements as achievement_count'])
            ->withSum('achievements as achievement_points', 'points')
            ->get()
            ->map(function ($u) {
                return [
                    'id'                 => $u->id,
                    'name'               => $u->name,
                    'avatar'             => $u->profile_photo_url,
                    'is_self'            => $u->id === auth()->id(),
                    'achievement_count'  => (int) ($u->achievement_count ?? 0),
                    'achievement_points' => (int) ($u->achievement_points ?? 0),
                    'monthly_score'      => $u->getWeightedPerformanceScore(now()->month, now()->year),
                ];
            })
            ->sortByDesc(fn ($u) => $u['achievement_points'] * 10 + $u['monthly_score'])
            ->values();

        // 9. Latest finalized formal evaluation (weighted appraisal breakdown)
        $latestEvaluation = \App\Models\Evaluation::withoutGlobalScopes()
            ->where('user_id', $user->id)
            ->where('status', 'finalized')
            ->orderByDesc('period_year')
            ->orderByDesc('period_month')
            ->with('scores')
            ->first();

        $evaluation = null;
        if ($latestEvaluation) {
            $evaluation = [
                'period_label'  => $latestEvaluation->period_label,
                'overall_score' => (float) $latestEvaluation->overall_score,
                'grade'         => \App\Services\EvaluationService::grade((float) $latestEvaluation->overall_score),
                'summary_note'  => $latestEvaluation->summary_note,
                'evaluator'     => optional($latestEvaluation->evaluator)->name,
                'scores'        => $latestEvaluation->scores->map(fn ($s) => [
                    'metric_name'      => $s->metric_name,
                    'category'         => $s->category,
                    'weight'           => (float) $s->weight,
                    'normalized_score' => $s->normalized_score !== null ? (float) $s->normalized_score : 0,
                    'weighted_score'   => $s->weighted_score !== null ? (float) $s->weighted_score : 0,
                    'is_auto'          => (bool) $s->is_auto,
                    'justification'    => $s->justification,
                ])->values(),
            ];
        }

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
            'evaluation'          => $evaluation,
            'heatmap'             => $heatmap,
            'employee'            => $user->only('id', 'name'),
            'earnedAchievements'  => $earnedAchievements,
            'lockedAchievements'  => $lockedAchievements,
            'totalAchievementPoints' => (int) $earnedAchievements->sum('points'),
            'leaderboard'         => $leaderboard,
        ]);
    }
}
