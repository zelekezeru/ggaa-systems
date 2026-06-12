<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\TaskComment;
use App\Models\User;
use App\Notifications\NewCommentNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

class TaskCommentController extends Controller
{
    /**
     * List comments for a task. The task is auto-scoped by the global Task scope,
     * so unauthorized users get a 404 from route-model binding.
     */
    public function index(Task $task)
    {
        $comments = $task->comments()
            ->with('user:id,name,profile_photo_path')
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(fn (TaskComment $c) => [
                'id'             => $c->id,
                'body'           => $c->body,
                'attachment_url' => $c->attachment_url,
                'user'           => $c->user ? [
                    'id'   => $c->user->id,
                    'name' => $c->user->name,
                    'avatar' => $c->user->profile_photo_url,
                ] : null,
                'created_at'     => $c->created_at->toISOString(),
            ]);

        return response()->json($comments);
    }

    /**
     * Post a new comment. Notifies all other participants in the conversation
     * (assignee, branch manager, super admins) with a NewCommentNotification.
     */
    public function store(Request $request, Task $task)
    {
        $validated = $request->validate([
            'body'       => 'required_without:attachment|nullable|string|max:5000',
            'attachment' => 'nullable|file|max:10240|mimes:pdf,jpg,jpeg,png,xlsx,docx,txt',
        ]);

        /** @var \App\Models\User $author */
        $author = Auth::user();

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $directory = 'task_comments/' . $task->id;
            // Private disk — served only via the auth-checked downloadAttachment route.
            $attachmentPath = $request->file('attachment')->store($directory, 'local');
        }

        $comment = $task->comments()->create([
            'user_id'         => $author->id,
            'body'            => $validated['body'] ?? '',
            'attachment_path' => $attachmentPath,
        ]);

        // Notify everyone interested except the author themselves.
        $recipients = collect();

        if ($task->assigned_user_id && $task->assigned_user_id !== $author->id) {
            $assignee = User::find($task->assigned_user_id);
            if ($assignee) $recipients->push($assignee);
        }

        $task->loadMissing('client.branch.manager');
        if ($task->client?->branch?->manager && $task->client->branch->manager->id !== $author->id) {
            $recipients->push($task->client->branch->manager);
        }

        if ($recipients->isNotEmpty()) {
            Notification::send($recipients->unique('id'), new NewCommentNotification($task, $comment, $author));
        }

        return back()->with('success', 'Comment posted.');
    }

    /**
     * Author or admin can delete a comment.
     */
    public function destroy(Task $task, TaskComment $comment)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        abort_unless(
            $comment->user_id === $user->id || $user->hasAnyRole(['Super Admin', 'Branch Manager']),
            403,
            'You cannot delete this comment.'
        );

        $comment->delete();

        return back()->with('success', 'Comment deleted.');
    }

    /**
     * Stream a comment attachment from the private disk. The Task global scope
     * (applied on route-model binding) guarantees the caller may see this task,
     * so visibility piggy-backs on it exactly like index().
     */
    public function downloadAttachment(Task $task, TaskComment $comment)
    {
        abort_unless($comment->task_id === $task->id, 404);
        abort_unless($comment->attachment_path, 404, 'No attachment on this comment.');
        abort_unless(Storage::disk('local')->exists($comment->attachment_path), 404, 'File not found.');

        return Storage::disk('local')->response($comment->attachment_path, basename($comment->attachment_path));
    }
}
