<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\TaskTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class TaskTemplateController extends Controller
{
    public function index()
    {
        abort_unless(Auth::user()->hasAnyRole(['Super Admin', 'Branch Manager']), 403);

        $templates = TaskTemplate::withCount('tasks')->get();

        $stats = [
            'total'     => $templates->count(),
            'monthly'   => $templates->where('frequency', 'Monthly')->count(),
            'quarterly' => $templates->where('frequency', 'Quarterly')->count(),
            'annually'  => $templates->where('frequency', 'Annually')->count(),
        ];

        return Inertia::render('SuperAdmin/TaskTypes', [
            'templates' => $templates,
            'stats'     => $stats,
        ]);
    }

    public function store(Request $request)
    {
        abort_unless(Auth::user()->hasAnyRole(['Super Admin', 'Branch Manager']), 403);

        $validated = $request->validate([
            'name'              => 'required|string|max:255|unique:task_templates,name',
            'frequency'         => 'required|in:Monthly,Quarterly,Annually',
            'due_date_offset'   => 'required|integer|min:1|max:28',
            'requires_document' => 'boolean',
        ]);

        TaskTemplate::create($validated);

        return back()->with('success', "Task type \"{$validated['name']}\" created successfully.");
    }

    public function update(Request $request, TaskTemplate $taskTemplate)
    {
        abort_unless(Auth::user()->hasAnyRole(['Super Admin', 'Branch Manager']), 403);

        $validated = $request->validate([
            'name'              => 'required|string|max:255|unique:task_templates,name,' . $taskTemplate->id,
            'frequency'         => 'required|in:Monthly,Quarterly,Annually',
            'due_date_offset'   => 'required|integer|min:1|max:28',
            'requires_document' => 'boolean',
        ]);

        $taskTemplate->update($validated);

        return back()->with('success', "Task type \"{$validated['name']}\" updated successfully.");
    }

    public function destroy(TaskTemplate $taskTemplate)
    {
        abort_unless(Auth::user()->hasAnyRole(['Super Admin']), 403);

        if ($taskTemplate->tasks()->exists()) {
            return back()->with('error', "Cannot delete \"{$taskTemplate->name}\" — it has active tasks linked to it.");
        }

        $taskTemplate->delete();

        return back()->with('success', "Task type deleted.");
    }
}
