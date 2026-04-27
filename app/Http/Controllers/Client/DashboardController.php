<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Task;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        // Load the client record linked to this portal user
        $client = Client::findOrFail($user->client_id);

        // Tasks that still need action (not Done), eager-loaded with their template
        $pendingTasks = Task::with('template')
            ->where('client_id', $client->id)
            ->whereNotIn('status', ['Done'])
            ->orderBy('due_date')
            ->get()
            ->map(fn($task) => [
                'id'        => $task->id,
                'due_date'  => $task->due_date?->format('d M Y'),
                'is_urgent' => $task->due_date && now()->diffInDays($task->due_date, false) <= 3,
                'template'  => ['name' => $task->template?->name ?? 'Task'],
            ]);

        $allTasks = Task::where('client_id', $client->id)->get();

        $stats = [
            'upcoming_count' => $allTasks->filter(
                fn($t) => $t->status !== 'Done' && $t->due_date && $t->due_date->isFuture()
            )->count(),
            'missing_docs'   => $allTasks->where('status', 'Pending Docs')->count(),
            'retainer'       => number_format($client->retainer_fee ?? 0, 2),
            'recent_docs'    => [], // Populated when document uploads are implemented
        ];

        return Inertia::render('Client/Dashboard', [
            'client'       => $client->only('id', 'company_name', 'tin_number'),
            'pendingTasks' => $pendingTasks->values(),
            'stats'        => $stats,
        ]);
    }
}
