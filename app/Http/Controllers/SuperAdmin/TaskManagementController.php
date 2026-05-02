<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Task;
use App\Models\TaskTemplate;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class TaskManagementController extends Controller
{
    public function index(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        abort_unless($user->hasAnyRole(['Super Admin', 'Branch Manager']), 403);

        // The Task model's global scope auto-filters Branch Managers to their branch.
        $query = Task::with(['client', 'template', 'assignedEmployee']);

        // -- Filters --
        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->filled('employee_id') && $request->employee_id !== 'all') {
            if ($request->employee_id === 'unassigned') {
                $query->whereNull('assigned_user_id');
            } else {
                $query->where('assigned_user_id', $request->employee_id);
            }
        }

        if ($request->filled('client_id') && $request->client_id !== 'all') {
            $query->where('client_id', $request->client_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('client', fn ($c) => $c->where('company_name', 'like', "%{$search}%"))
                  ->orWhereHas('template', fn ($t) => $t->where('name', 'like', "%{$search}%"));
            });
        }

        // DB-agnostic ordering (works on MySQL + SQLite, unlike FIELD())
        $tasks = $query->orderByRaw($this->statusOrderingSql())
            ->orderBy('due_date')
            ->get();

        // Employee/client lookups also respect the global scope so Branch Managers only
        // see their own branch's people and clients.
        $employeesQuery = User::role('Employee');
        if ($user->hasRole('Branch Manager') && ! $user->hasRole('Super Admin')) {
            $employeesQuery->where('branch_id', $user->branch_id);
        }
        $employees = $employeesQuery->get(['id', 'name', 'branch_id'])->map(function ($u) {
            $u->current_load = $u->getCurrentCapacityLoad();
            $u->capacity_percent = $u->capacity_percent;
            return $u;
        });
        $clients   = Client::get(['id', 'company_name', 'complexity_score']);
        $templates = TaskTemplate::all(['id', 'name']);

        $statsBase = Task::query();
        $stats = [
            'total'      => (clone $statsBase)->count(),
            'unassigned' => (clone $statsBase)->whereNull('assigned_user_id')->count(),
            'in_review'  => (clone $statsBase)->where('status', 'In Review')->count(),
            'overdue'    => (clone $statsBase)
                ->where('status', '!=', 'Done')
                ->where('due_date', '<', now())
                ->count(),
        ];

        return Inertia::render('SuperAdmin/Tasks', [
            'tasks'     => $tasks,
            'employees' => $employees,
            'clients'   => $clients,
            'templates' => $templates,
            'stats'     => $stats,
            'filters'   => $request->only(['status', 'employee_id', 'client_id', 'search']),
        ]);
    }

    /**
     * Cross-DB compatible ordering for the task status column.
     * Works on MySQL, PostgreSQL, and SQLite (unlike MySQL-specific FIELD()).
     */
    private function statusOrderingSql(): string
    {
        return "CASE status "
            . "WHEN 'To Do' THEN 1 "
            . "WHEN 'In Review' THEN 2 "
            . "WHEN 'Waiting on Client' THEN 3 "
            . "WHEN 'Done' THEN 4 "
            . "ELSE 5 END";
    }

    public function store(Request $request)
    {
        abort_unless(Auth::user()->hasAnyRole(['Super Admin', 'Branch Manager']), 403);

        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'task_template_id' => 'required|exists:task_templates,id',
            'due_date' => 'required|date',
            'assigned_user_id' => 'nullable|exists:users,id',
            'status' => 'required|in:Waiting on Client,To Do,In Review,Done',
            'notes' => 'nullable|string|max:1000',
        ]);

        Task::create($validated);

        return back()->with('success', 'Task created successfully.');
    }

    public function update(Request $request, Task $task)
    {
        abort_unless(Auth::user()->hasAnyRole(['Super Admin', 'Branch Manager']), 403);

        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'task_template_id' => 'required|exists:task_templates,id',
            'due_date' => 'required|date',
            'assigned_user_id' => 'nullable|exists:users,id',
            'status' => 'required|in:Waiting on Client,To Do,In Review,Done',
            'notes' => 'nullable|string|max:1000',
        ]);

        $task->update($validated);

        return back()->with('success', 'Task updated successfully.');
    }

    public function destroy(Task $task)
    {
        abort_unless(Auth::user()->hasAnyRole(['Super Admin']), 403);

        $task->delete();

        return back()->with('success', 'Task deleted.');
    }
}
