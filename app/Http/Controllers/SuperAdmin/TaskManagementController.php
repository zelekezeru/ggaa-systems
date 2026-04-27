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
        abort_unless(Auth::user()->hasAnyRole(['Super Admin', 'Branch Manager']), 403);

        $query = Task::with(['client', 'template', 'assignedEmployee'])
            ->withoutGlobalScopes(); // Admin sees all tasks regardless of role

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
            $query->whereHas('client', fn($q) => $q->where('company_name', 'like', "%{$search}%"))
                ->orWhereHas('template', fn($q) => $q->where('name', 'like', "%{$search}%"));
        }

        $tasks = $query->orderByRaw("FIELD(status, 'To Do', 'In Review', 'Waiting on Client', 'Done')")
            ->orderBy('due_date')
            ->get();

        $employees = User::role('Employee')->get(['id', 'name', 'branch_id']);
        $clients = Client::withoutGlobalScopes()->get(['id', 'company_name', 'complexity_score']);
        $templates = TaskTemplate::all(['id', 'name']);

        // Summary stats
        $stats = [
            'total' => Task::withoutGlobalScopes()->count(),
            'unassigned' => Task::withoutGlobalScopes()->whereNull('assigned_user_id')->count(),
            'in_review' => Task::withoutGlobalScopes()->where('status', 'In Review')->count(),
            'overdue' => Task::withoutGlobalScopes()
                ->where('status', '!=', 'Done')
                ->where('due_date', '<', now())
                ->count(),
        ];


        return Inertia::render('SuperAdmin/Tasks', [
            'tasks' => $tasks,
            'employees' => $employees,
            'clients' => $clients,
            'templates' => $templates,
            'stats' => $stats,
            'filters' => $request->only(['status', 'employee_id', 'client_id', 'search']),
        ]);
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
