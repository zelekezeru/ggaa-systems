<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class MessageController extends Controller
{
    // ── GET: Client reads their message thread ────────────────────────────────
    public function indexForClient()
    {
        $user = Auth::user();

        $messages = Message::with('sender:id,name,profile_photo_path')
            ->where('client_id', $user->client_id)
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(fn ($m) => [
                'id'          => $m->id,
                'body'        => $m->body,
                'sender'      => $m->sender ? ['id' => $m->sender->id, 'name' => $m->sender->name] : null,
                'is_mine'     => $m->sender_id === $user->id,
                'attachment'  => $m->attachment_path ? Storage::url($m->attachment_path) : null,
                'created_at'  => $m->created_at->toISOString(),
            ]);

        // Mark all employee messages as read by client
        Message::where('client_id', $user->client_id)
            ->where('is_read_by_client', false)
            ->whereNot('sender_id', $user->id)
            ->update(['is_read_by_client' => true]);

        // Mark related notifications as read
        Auth::user()->unreadNotifications()
            ->where('type', 'App\Notifications\NewMessageNotification')
            ->whereJsonContains('data->client_id', $user->client_id)
            ->update(['read_at' => now()]);

        $client = Client::find($user->client_id, ['id', 'company_name']);

        return Inertia::render('Client/Messages', [
            'messages' => $messages,
            'client'   => $client,
        ]);
    }

    // ── GET: Employee reads a specific client's thread ────────────────────────
    public function indexForEmployee(Client $client)
    {
        $user = Auth::user();

        // Guard: employee can only read threads for clients assigned to them
        abort_unless(
            $client->assigned_employee_id === $user->id || $user->hasRole('Branch Manager') || $user->hasRole('Super Admin'),
            403,
            'You are not assigned to this client.'
        );

        $messages = Message::with('sender:id,name,profile_photo_path')
            ->where('client_id', $client->id)
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(fn ($m) => [
                'id'         => $m->id,
                'body'       => $m->body,
                'sender'     => $m->sender ? ['id' => $m->sender->id, 'name' => $m->sender->name] : null,
                'is_mine'    => $m->sender_id === $user->id,
                'attachment' => $m->attachment_path ? Storage::url($m->attachment_path) : null,
                'created_at' => $m->created_at->toISOString(),
            ]);

        // Mark all client messages as read by employee
        Message::where('client_id', $client->id)
            ->where('is_read_by_employee', false)
            ->update(['is_read_by_employee' => true]);

        // Mark related notifications as read
        Auth::user()->unreadNotifications()
            ->where('type', 'App\Notifications\NewMessageNotification')
            ->whereJsonContains('data->client_id', $client->id)
            ->update(['read_at' => now()]);

        // Also pass list of all assigned clients for the sidebar switcher
        $assignedClients = Client::where('assigned_employee_id', $user->id)
            ->select('id', 'company_name')
            ->get();

        return Inertia::render('Employee/Messages', [
            'messages'        => $messages,
            'client'          => $client->only('id', 'company_name'),
            'assignedClients' => $assignedClients,
        ]);
    }

    // ── POST: Client sends a message to the Firm ──────────────────────────────
    public function storeFromClient(Request $request)
    {
        $request->validate([
            'body'       => 'required_without:attachment|nullable|string',
            'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $user     = Auth::user();
        $filePath = null;

        if ($request->hasFile('attachment')) {
            $directory = 'client_files/' . $user->client->tin_number;
            $filePath  = $request->file('attachment')->store($directory, 'public');
        }

        $message = Message::create([
            'client_id'           => $user->client_id,
            'sender_id'           => $user->id,
            'body'                => $request->body,
            'attachment_path'     => $filePath,
            'is_read_by_employee' => false,
        ]);

        // Notify assigned staff
        $client = $user->client;
        if ($client && $client->assignedEmployee) {
            $client->assignedEmployee->notify(new \App\Notifications\ClientMessageReceived($client, $message));
        }

        return back()->with('success', 'Message sent.');
    }

    // ── POST: Employee replies to a Client ────────────────────────────────────
    public function storeFromEmployee(Request $request, $client_id)
    {
        $request->validate([
            'body'       => 'required_without:attachment|nullable|string',
            'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png,xlsx,docx|max:10240',
        ]);

        $filePath = null;

        if ($request->hasFile('attachment')) {
            $filePath = $request->file('attachment')->store('firm_files/outbound', 'public');
        }

        $message = Message::create([
            'client_id'         => $client_id,
            'sender_id'         => Auth::id(),
            'body'              => $request->body,
            'attachment_path'   => $filePath,
            'is_read_by_client' => false,
        ]);

        // Notify the client
        $client = Client::find($client_id);
        if ($client && $client->user) {
            $client->user->notify(new \App\Notifications\NewMessageNotification($message, Auth::user()));
        }

        return back()->with('success', 'Reply sent to client.');
    }
}
