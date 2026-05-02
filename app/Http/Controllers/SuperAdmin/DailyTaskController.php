<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDailyTaskRequest;
use App\Models\Branch;
use App\Models\DailyTask;
use App\Models\User;
use App\Notifications\DailyTaskAssignedNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class DailyTaskController extends Controller
{
    public function index(Request $request)
    {
        /** @var User $actor */
        $actor = Auth::user();
        abort_unless($actor->hasAnyRole(['Super Admin', 'Branch Manager']), 403);

        $date = $request->get('date', today()->toDateString());

        $query = DailyTask::with(['assignedTo', 'assignedBy'])
            ->withoutGlobalScopes()
            ->whereDate('scheduled_date', $date);

        if ($actor->hasRole('Branch Manager') && ! $actor->hasRole('Super Admin')) {
            $query->where('branch_id', $actor->branch_id);
        }

        if ($request->filled('employee_id') && $request->employee_id !== 'all') {
            $query->where('assigned_to', $request->employee_id);
        }

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->filled('priority') && $request->priority !== 'all') {
            $query->where('priority', $request->priority);
        }

        $tasks = $query
            ->orderByRaw("CASE priority WHEN 'urgent' THEN 1 WHEN 'normal' THEN 2 ELSE 3 END")
            ->orderBy('scheduled_time')
            ->get();

        $employeesQuery = User::role('Employee');
        if ($actor->hasRole('Branch Manager') && ! $actor->hasRole('Super Admin')) {
            $employeesQuery->where('branch_id', $actor->branch_id);
        }
        $employees = $employeesQuery->get(['id', 'name', 'branch_id']);

        $branches = $actor->hasRole('Super Admin')
            ? Branch::all(['id', 'name'])
            : Branch::where('id', $actor->branch_id)->get(['id', 'name']);

        // Stats scoped to current actor and selected date
        $statsBase = DailyTask::withoutGlobalScopes()->whereDate('scheduled_date', $date);
        if ($actor->hasRole('Branch Manager') && ! $actor->hasRole('Super Admin')) {
            $statsBase->where('branch_id', $actor->branch_id);
        }

        $stats = [
            'total'   => (clone $statsBase)->count(),
            'pending' => (clone $statsBase)->where('status', 'pending')->count(),
            'done'    => (clone $statsBase)->where('status', 'done')->count(),
            'urgent'  => (clone $statsBase)->where('priority', 'urgent')->count(),
        ];

        return Inertia::render('SuperAdmin/DailyTasks', [
            'tasks'     => $tasks,
            'employees' => $employees,
            'branches'  => $branches,
            'stats'     => $stats,
            'filters'   => array_merge(
                ['date' => $date, 'employee_id' => null, 'status' => null, 'priority' => null],
                $request->only(['date', 'employee_id', 'status', 'priority'])
            ),
        ]);
    }

    public function store(StoreDailyTaskRequest $request)
    {
        /** @var User $actor */
        $actor = Auth::user();

        /** @var DailyTask $task */
        $task = DailyTask::create(array_merge(
            $request->validated(),
            ['assigned_by' => $actor->id]
        ));

        $task->load('assignedBy');

        /** @var User $assignee */
        $assignee = User::find($request->validated('assigned_to'));
        $assignee->notify(new DailyTaskAssignedNotification($task));

        return back()->with('success', "Task \"{$task->title}\" assigned to {$assignee->name}.");
    }

    public function updateStatus(Request $request, DailyTask $dailyTask)
    {
        /** @var User $actor */
        $actor = Auth::user();

        // Employee can only update their own; admin/manager can update any in scope
        if ($actor->hasRole('Employee')) {
            abort_unless($dailyTask->assigned_to === $actor->id, 403);
        } else {
            abort_unless($actor->hasAnyRole(['Super Admin', 'Branch Manager']), 403);
        }

        $validated = $request->validate([
            'status' => ['required', 'in:pending,in_progress,done,cancelled'],
            'notes'  => ['nullable', 'string', 'max:1000'],
        ]);

        $data = ['status' => $validated['status']];

        if (isset($validated['notes'])) {
            $data['notes'] = $validated['notes'];
        }

        if ($validated['status'] === 'done') {
            $data['completed_at'] = now();
        }

        $dailyTask->update($data);

        return back()->with('success', 'Task status updated.');
    }

    public function destroy(DailyTask $dailyTask)
    {
        /** @var User $actor */
        $actor = Auth::user();
        abort_unless($actor->hasAnyRole(['Super Admin', 'Branch Manager']), 403);

        $dailyTask->delete();

        return back()->with('success', 'Task removed.');
    }
}
