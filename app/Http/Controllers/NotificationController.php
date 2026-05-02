<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class NotificationController extends Controller
{
    /**
     * Return the authenticated user's latest unread notifications as JSON.
     * Used by the NotificationBell component via axios.
     */
    public function index()
    {
        $notifications = Auth::user()
            ->notifications()
            ->latest()
            ->take(20)
            ->get()
            ->map(fn ($n) => [
                'id'        => $n->id,
                'type'      => class_basename($n->type),
                'data'      => $n->data,
                'read_at'   => $n->read_at?->toISOString(),
                'created_at'=> $n->created_at->diffForHumans(),
            ]);

        return response()->json($notifications);
    }

    /**
     * Mark all of the user's unread notifications as read.
     */
    public function markAllRead()
    {
        Auth::user()->unreadNotifications->markAsRead();

        return response()->json(['ok' => true]);
    }

    /**
     * Mark a single notification as read by its UUID.
     */
    public function markRead(string $id)
    {
        $notification = Auth::user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        return response()->json(['ok' => true]);
    }

    /**
     * Delete a single notification (dismiss permanently).
     */
    public function destroy(string $id)
    {
        Auth::user()->notifications()->where('id', $id)->delete();

        return response()->json(['ok' => true]);
    }

    /**
     * Inertia inbox page — full history view with pagination.
     */
    public function inbox(Request $request)
    {
        $user = Auth::user();
        $perPage = max(10, min(100, (int) $request->integer('per_page', 25)));

        $notifications = $user->notifications()
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->through(fn ($n) => [
                'id'         => $n->id,
                'type'       => $n->data['type'] ?? class_basename($n->type),
                'title'      => $n->data['title'] ?? 'Notification',
                'body'       => $n->data['body'] ?? '',
                'icon'       => $n->data['icon'] ?? '🔔',
                'url'        => $n->data['url'] ?? null,
                'read_at'    => $n->read_at?->toISOString(),
                'created_at' => $n->created_at->toISOString(),
                'data'       => $n->data,
            ]);

        return Inertia::render('Notifications/Inbox', [
            'notifications' => $notifications,
            'unreadCount'   => $user->unreadNotifications()->count(),
        ]);
    }
}
