<?php

namespace App\Http\Controllers\TeamProject;

use App\Http\Controllers\Controller;
use App\Models\TeamProject;
use App\Models\TeamProjectMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TeamProjectMessageController extends Controller
{
    public function store(Request $request, TeamProject $teamProject)
    {
        $this->authorizeMember($teamProject);

        $validated = $request->validate([
            'body'       => 'required|string|max:5000',
            'attachment' => 'nullable|file|max:10240',
        ]);

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')
                ->store("team_projects/{$teamProject->id}/chat", 'public');
        }

        TeamProjectMessage::create([
            'team_project_id' => $teamProject->id,
            'sender_id'       => Auth::id(),
            'body'            => $validated['body'],
            'attachment_path' => $attachmentPath,
        ]);

        return back();
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
}
