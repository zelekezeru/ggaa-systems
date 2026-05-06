<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Branch;
use App\Models\ServiceType;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;

class AdminClientController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(auth()->user()->hasAnyRole(['Super Admin', 'Branch Manager']), 403, 'Unauthorized access.');

        $query = Client::with(['branch', 'assignedEmployee', 'serviceTypes', 'user']);

        // Filtering by service type
        if ($request->has('service_type')) {
            $query->whereHas('serviceTypes', function($q) use ($request) {
                $q->where('slug', $request->service_type);
            });
        }

        $clients = $query->get()->map(function ($client) {
            return [
                'id' => $client->id,
                'company_name' => $client->company_name,
                'tin_number' => $client->tin_number,
                'email' => $client->user?->email,
                'sector' => $client->sector,
                'branch_id' => $client->branch_id,
                'assigned_employee_id' => $client->assigned_employee_id,
                'complexity_score' => $client->complexity_score,
                'status' => $client->status,
                'logo_url' => $client->logo_url,
                'branch' => $client->branch,
                'assigned_employee' => $client->assignedEmployee,
                'service_types' => $client->serviceTypes,
            ];
        });
        $branches = Branch::all(['id', 'name']);
        $serviceTypes = ServiceType::where('is_active', true)->get(['id', 'name', 'slug']);
        
        $employees = User::role('Employee')->get(['id', 'name', 'branch_id']);

        return Inertia::render('SuperAdmin/Clients', [
            'clients' => $clients,
            'branches' => $branches,
            'employees' => $employees,
            'serviceTypes' => $serviceTypes,
        ]);
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->hasAnyRole(['Super Admin', 'Branch Manager']), 403, 'Unauthorized access.');

        $validated = $request->validate([
            'company_name' => 'required|string|max:255',
            'tin_number' => 'required|string|max:255|unique:clients,tin_number',
            'email' => 'required|email|max:255|unique:users,email',
            'sector' => 'required|string|max:255',
            'service_type_ids' => 'required|array|min:1',
            'service_type_ids.*' => 'exists:service_types,id',
            'branch_id' => 'required|exists:branches,id',
            'assigned_employee_id' => 'nullable|exists:users,id',
            'complexity_score' => 'required|integer|min:1|max:10',
            'status' => 'required|in:Active,Risk,Incomplete',
            'logo' => 'nullable|image|max:10000',
        ]);

        $logoPath = null;
        if ($request->hasFile('logo')) {
            $logoPath = $request->file('logo')->store('client-logos', 'public');
        }

        $data = collect($validated)->except(['service_type_ids', 'logo'])->toArray();
        $data['logo_path'] = $logoPath;
        $client = Client::create($data);
        $client->serviceTypes()->sync($request->service_type_ids);

        // Automate Portal User Creation
        $user = User::create([
            'name' => $client->company_name,
            'email' => $request->email,
            'password' => Hash::make($client->tin_number),
            'client_id' => $client->id,
            'email_verified_at' => now(),
        ]);
        $user->assignRole('Client');

        return back()->with('success', 'Client onboarded and portal access granted.');
    }

    public function update(Request $request, Client $client)
    {
        abort_unless(auth()->user()->hasAnyRole(['Super Admin', 'Branch Manager']), 403, 'Unauthorized access.');

        $validated = $request->validate([
            'company_name' => 'required|string|max:255',
            'tin_number' => [
                'required',
                'string',
                'max:255',
                Rule::unique('clients')->ignore($client->id)
            ],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users')->ignore($client->user?->id)
            ],
            'sector' => 'required|string|max:255',
            'service_type_ids' => 'required|array|min:1',
            'service_type_ids.*' => 'exists:service_types,id',
            'branch_id' => 'required|exists:branches,id',
            'assigned_employee_id' => 'nullable|exists:users,id',
            'complexity_score' => 'required|integer|min:1|max:10',
            'status' => 'required|in:Active,Risk,Incomplete',
            'logo' => 'nullable|image|max:10000',
        ]);

        if ($request->hasFile('logo')) {
            if ($client->logo_path) {
                Storage::disk('public')->delete($client->logo_path);
            }
            $client->logo_path = $request->file('logo')->store('client-logos', 'public');
            $client->save();
        }

        $data = collect($validated)->except(['service_type_ids', 'logo'])->toArray();
        $client->update($data);
        $client->serviceTypes()->sync($request->service_type_ids);

        // Update linked user email if it exists
        $user = User::where('client_id', $client->id)->first();
        if ($user) {
            $user->update([
                'email' => $request->email,
                'name' => $client->company_name,
            ]);
        }

        return back()->with('success', 'Client updated successfully.');
    }

    public function destroy(Client $client)
    {
        abort_unless(auth()->user()->hasAnyRole(['Super Admin']), 403, 'Unauthorized access.');

        $client->delete();

        return back()->with('success', 'Client deleted successfully.');
    }

    public function resetPassword(Client $client)
    {
        abort_unless(auth()->user()->hasAnyRole(['Super Admin', 'Branch Manager']), 403, 'Unauthorized access.');

        $user = $client->user;
        if (!$user) {
            return back()->with('error', 'No portal user found for this client.');
        }

        // Send reset link
        try {
            $status = \Illuminate\Support\Facades\Password::sendResetLink(['email' => $user->email]);
            
            return $status === \Illuminate\Support\Facades\Password::RESET_LINK_SENT
                ? back()->with('success', 'Password reset link sent to ' . $user->email)
                : back()->with('error', 'Unable to send reset link.');
        } catch (\Exception $e) {
            // Fallback: reset to TIN if mail is not configured
            $user->update(['password' => \Illuminate\Support\Facades\Hash::make($client->tin_number)]);
            return back()->with('success', 'Portal password reset to TIN number (email failed to send).');
        }
    }
}
