<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Message;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class MessageController extends Controller
{
    // 1. When a CLIENT sends a message to the Firm
    public function storeFromClient(Request $request)
    {
        $request->validate([
            'body' => 'required_without:attachment|string',
            'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120', // Max 5MB
        ]);

        $user = Auth::user();
        $filePath = null;

        // Handle the secure file upload
        if ($request->hasFile('attachment')) {
            // Saves to storage/app/public/client_files/TIN_NUMBER/...
            $directory = 'client_files/' . $user->client->tin_number;
            $filePath = $request->file('attachment')->store($directory, 'public');
        }

        // Create the message
        Message::create([
            'client_id' => $user->client_id, // Safely locked to this specific client
            'sender_id' => $user->id,
            'body' => $request->body,
            'attachment_path' => $filePath,
            'is_read_by_employee' => false,
        ]);

        // Inertia automatically returns the user to the same page with the updated data
        return back()->with('success', 'Message and files securely sent to your accountant.');
    }

    // 2. When an EMPLOYEE replies to a Client
    public function storeFromEmployee(Request $request, $client_id)
    {
        $request->validate([
            'body' => 'required_without:attachment|string',
            'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png,xlsx,docx|max:10240', // Max 10MB
        ]);

        $filePath = null;

        if ($request->hasFile('attachment')) {
            $filePath = $request->file('attachment')->store('firm_files/outbound', 'public');
        }

        Message::create([
            'client_id' => $client_id,
            'sender_id' => Auth::id(),
            'body' => $request->body,
            'attachment_path' => $filePath,
            'is_read_by_client' => false,
        ]);

        return back()->with('success', 'Reply sent to client.');
    }
}
