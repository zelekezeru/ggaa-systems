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
}
