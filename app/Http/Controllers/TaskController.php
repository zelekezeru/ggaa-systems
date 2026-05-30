<?php

namespace App\Http\Controllers;

use App\Exceptions\CapacityThresholdExceededException;
use App\Http\Requests\AssignTaskRequest;
use App\Models\Message;
use App\Models\Task;
use App\Models\User;
use App\Services\TaskAssignmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TaskController extends Controller
{
    public function __construct(private readonly TaskAssignmentService $assignmentService)
    {
    }

    /**
     * Assign a task to an employee via the admin dashboard.
     * Delegates capacity validation to TaskAssignmentService.
     */
    public function assign(AssignTaskRequest $request, Task $task)
    {
        $employee = User::findOrFail($request->validated('employee_id'));

        try {
            $this->assignmentService->assign($task, $employee);
        } catch (CapacityThresholdExceededException $e) {
            return back()->withErrors([
                'employee_id' => $e->getMessage(),
            ])->withInput();
        }

        return back()->with('success', "{$employee->name} has been assigned to this task.");
    }

    /**
     * Unassign a task, returning it to the unassigned pool.
     */
    public function unassign(Task $task)
    {
        /** @var User $actor */
        $actor = Auth::user();
        abort_unless($actor->hasRole(['Super Admin', 'Branch Manager']), 403);

        $this->assignmentService->unassign($task);

        return back()->with('success', 'Task unassigned and returned to the queue.');
    }

    /**
     * Accept a finalized ERCA tax document upload from an employee.
     * Moves the task to "In Review" for manager sign-off.
     */
    public function uploadReport(Request $request, Task $task)
    {
        $request->validate([
            'attachment' => ['required', 'file', 'mimes:pdf,jpg,png,xlsx', 'max:10240'],
            'comment'    => ['nullable', 'string', 'max:500'],
        ]);

        /** @var User $actor */
        $actor = Auth::user();

        // Only the assigned employee or an admin-level role may upload.
        if (
            $task->assigned_user_id !== $actor->id
            && ! $actor->hasRole(['Super Admin', 'Branch Manager'])
        ) {
            abort(403, 'You are not authorized to submit documents for this task.');
        }

        // Organize documents under a TIN-scoped private vault: client_vault/{TIN}/{YYYY-MM}/
        $directory = 'client_vault/' . ($task->client?->tin_number ?? 'unassigned') . '/' . now()->format('Y-m');
        $path      = $request->file('attachment')->store($directory, 'local');

        $existingPaths = is_array($task->document_path) ? $task->document_path : [];

        $task->update([
            'status'        => 'In Review',
            'document_path' => array_merge($existingPaths, [$path]),
        ]);

        if ($request->filled('comment')) {
            Message::create([
                'sender_id' => $actor->id,
                'client_id' => $task->client_id,
                'body'      => $request->comment,
            ]);
        }

        return back()->with('success', 'Document submitted. Task moved to In Review.');
    }

    /**
     * Securely download task report documents.
     * Integrates with the Task model's global scope to enforce tenant/role access.
     */
    public function downloadDocument(Request $request, Task $task)
    {
        $path = $request->query('path');

        $existingPaths = is_array($task->document_path) ? $task->document_path : [];
        if (!in_array($path, $existingPaths, true)) {
            abort(404, 'File not found in task attachments.');
        }

        if (!\Illuminate\Support\Facades\Storage::disk('local')->exists($path)) {
            abort(404, 'File does not exist on disk.');
        }

        return \Illuminate\Support\Facades\Storage::disk('local')->download($path, basename($path));
    }
}
