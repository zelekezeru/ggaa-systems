<?php

namespace App\Http\Controllers\TeamProject;

use App\Http\Controllers\Controller;
use App\Models\TeamProject;
use App\Models\TeamProjectFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class TeamProjectFileController extends Controller
{
    public function store(Request $request, TeamProject $teamProject)
    {
        $this->authorizeMember($teamProject);

        $request->validate([
            'file' => 'required|file|max:100000', // 20 MB
        ]);

        $upload   = $request->file('file');
        $folder   = "team_projects/{$teamProject->id}";
        $stored   = $upload->store($folder, 'public');

        TeamProjectFile::create([
            'team_project_id' => $teamProject->id,
            'uploaded_by'     => Auth::id(),
            'original_name'   => $upload->getClientOriginalName(),
            'path'            => $stored,
            'mime_type'       => $upload->getMimeType(),
            'size_bytes'      => $upload->getSize(),
        ]);

        return back()->with('success', 'File uploaded.');
    }

    public function destroy(TeamProject $teamProject, TeamProjectFile $file)
    {
        abort_unless($file->team_project_id === $teamProject->id, 404);

        $user = Auth::user();
        $isLeader = $teamProject->team_leader_id === $user->id;
        $isUploader = $file->uploaded_by === $user->id;
        abort_unless($user->can('manage team projects') || $isLeader || $isUploader, 403);

        if ($file->path) {
            Storage::disk('public')->delete($file->path);
        }
        $file->delete();

        return back()->with('success', 'File removed.');
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
