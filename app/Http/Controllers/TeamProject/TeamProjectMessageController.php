<?php

namespace App\Http\Controllers\TeamProject;

use App\Http\Controllers\Controller;
use App\Models\TeamProject;
use App\Models\TeamProjectMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

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
            // Private disk — served only via the auth-checked downloadAttachment route.
            $attachmentPath = $request->file('attachment')
                ->store("team_projects/{$teamProject->id}/chat", 'local');
        }

        TeamProjectMessage::create([
            'team_project_id' => $teamProject->id,
            'sender_id'       => Auth::id(),
            'body'            => $validated['body'],
            'attachment_path' => $attachmentPath,
        ]);

        return back();
    }

    public function downloadAttachment(TeamProject $teamProject, TeamProjectMessage $message)
    {
        abort_unless($message->team_project_id === $teamProject->id, 404);
        $this->authorizeMember($teamProject);

        abort_unless($message->attachment_path, 404, 'No attachment on this message.');
        abort_unless(Storage::disk('local')->exists($message->attachment_path), 404, 'File not found.');

        return Storage::disk('local')->response($message->attachment_path, basename($message->attachment_path));
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
