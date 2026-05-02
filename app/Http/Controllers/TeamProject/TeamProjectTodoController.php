<?php

namespace App\Http\Controllers\TeamProject;

use App\Http\Controllers\Controller;
use App\Models\TeamProject;
use App\Models\TeamProjectTodo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TeamProjectTodoController extends Controller
{
    public function store(Request $request, TeamProject $teamProject)
    {
        $this->authorizeMember($teamProject);

        $validated = $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'assigned_to' => 'nullable|exists:users,id',
            'due_date'    => 'nullable|date',
        ]);

        TeamProjectTodo::create([
            'team_project_id' => $teamProject->id,
            'created_by'      => Auth::id(),
            'title'           => $validated['title'],
            'description'     => $validated['description'] ?? null,
            'assigned_to'     => $validated['assigned_to'] ?? null,
            'due_date'        => $validated['due_date'] ?? null,
            'order_index'     => (int) TeamProjectTodo::where('team_project_id', $teamProject->id)->max('order_index') + 1,
            'status'          => 'todo',
        ]);

        return back()->with('success', 'Todo added to plan.');
    }

    public function update(Request $request, TeamProject $teamProject, TeamProjectTodo $todo)
    {
        abort_unless($todo->team_project_id === $teamProject->id, 404);
        $this->authorizeMember($teamProject);

        $validated = $request->validate([
            'title'       => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'assigned_to' => 'nullable|exists:users,id',
            'due_date'    => 'nullable|date',
            'status'      => 'sometimes|required|in:todo,in_progress,done',
        ]);

        if (isset($validated['status'])) {
            if ($validated['status'] === 'done' && $todo->status !== 'done') {
                $validated['completed_at'] = now();
            } elseif ($validated['status'] !== 'done') {
                $validated['completed_at'] = null;
            }
        }

        $todo->update($validated);

        return back()->with('success', 'Todo updated.');
    }

    public function destroy(TeamProject $teamProject, TeamProjectTodo $todo)
    {
        abort_unless($todo->team_project_id === $teamProject->id, 404);
        $this->authorizeManage($teamProject);

        $todo->delete();

        return back()->with('success', 'Todo removed.');
    }

    private function authorizeMember(TeamProject $teamProject): void
    {
        $user = Auth::user();
        if ($user->can('manage team projects') || $teamProject->team_leader_id === $user->id) {
            return;
        }
        $isMember = $teamProject->activeMembers()->where('user_id', $user->id)->exists();
        abort_unless($isMember, 403);
    }

    private function authorizeManage(TeamProject $teamProject): void
    {
        $user = Auth::user();
        if ($user->can('manage team projects') || $teamProject->team_leader_id === $user->id) {
            return;
        }
        abort(403);
    }
}
