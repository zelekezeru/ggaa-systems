<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ClientPortalController extends Controller
{
    public function showTask(Task $task)
    {
        // Global scope handles security (only seeing own tasks)
        $task->load(['template', 'comments.user']);

        return Inertia::render('Client/TaskShow', [
            'task' => $task,
        ]);
    }

    /**
     * Allow the client to upload supporting documents to their own task.
     * The Task global scope ensures they can only touch their own client's tasks.
     */
    public function uploadFiles(Request $request, Task $task)
    {
        $request->validate([
            'files'   => ['required', 'array', 'max:5'],
            'files.*' => ['file', 'mimes:pdf,jpg,jpeg,png,xlsx,docx', 'max:10240'],
        ]);

        $directory     = 'client_vault/' . ($task->client?->tin_number ?? 'unassigned') . '/' . now()->format('Y-m');
        $existingPaths = is_array($task->document_path) ? $task->document_path : [];
        $newPaths      = [];

        foreach ($request->file('files') as $file) {
            $newPaths[] = $file->store($directory, 'local');
        }

        $task->update(['document_path' => array_merge($existingPaths, $newPaths)]);

        return back()->with('success', 'Files uploaded successfully.');
    }
}
