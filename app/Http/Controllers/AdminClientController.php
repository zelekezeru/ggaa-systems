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

        $query = Client::with(['branch', 'assignedEmployee', 'serviceTypes', 'user', 'legalStructure', 'bankAccounts']);

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
                'trade_license_number' => $client->trade_license_number,
                'address' => $client->address,
                'tax_center' => $client->tax_center,
                'legal_structure_id' => $client->legal_structure_id,
                'legal_structure' => $client->legalStructure,
                'owner_name' => $client->owner_name,
                'phone' => $client->phone,
                'email' => $client->user?->email,
                'etrade_email' => $client->etrade_email,
                'has_etrade_password' => !empty($client->etrade_password),
                'venture' => $client->venture,
                'year_established' => $client->year_established,
                'sector' => $client->sector,
                'branch_id' => $client->branch_id,
                'assigned_employee_id' => $client->assigned_employee_id,
                'complexity_score' => $client->complexity_score,
                'status' => $client->status,
                'logo_url' => $client->logo_url,
                'branch' => $client->branch,
                'assigned_employee' => $client->assignedEmployee,
                'service_types' => $client->serviceTypes,
                'bank_accounts' => $client->bankAccounts,
            ];
        });
        
        $branches = Branch::orderBy('name', 'asc')->get(['id', 'name']);
        $serviceTypes = ServiceType::where('is_active', true)->orderBy('name', 'asc')->get(['id', 'name', 'slug']);
        $employees = User::role('Employee')->orderBy('name', 'asc')->get(['id', 'name', 'email', 'branch_id']);
        $legalStructures = \App\Models\LegalStructure::orderBy('name', 'asc')->get(['id', 'name', 'description']);

        return Inertia::render('SuperAdmin/Clients', [
            'clients' => $clients,
            'branches' => $branches,
            'employees' => $employees,
            'serviceTypes' => $serviceTypes,
            'legalStructures' => $legalStructures,
        ]);
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->hasAnyRole(['Super Admin', 'Branch Manager']), 403, 'Unauthorized access.');

        $validated = $request->validate([
            'company_name' => 'required|string|max:255',
            'tin_number' => 'required|string|max:255|unique:clients,tin_number',
            'trade_license_number' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'tax_center' => 'nullable|string|max:255',
            'legal_structure_id' => 'nullable|exists:legal_structures,id',
            'owner_name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'etrade_email' => 'nullable|string|max:255',
            'etrade_password' => 'nullable|string',
            'venture' => 'nullable|string|max:255',
            'year_established' => 'nullable|integer',
            'sector' => 'required|string|max:255',
            'service_type_ids' => 'required|array|min:1',
            'service_type_ids.*' => 'exists:service_types,id',
            'branch_id' => 'required|exists:branches,id',
            'assigned_employee_id' => 'nullable|exists:users,id',
            'complexity_score' => 'required|integer|min:1|max:10',
            'status' => 'required|in:Active,Risk,Incomplete',
            'logo' => 'nullable|image|max:10000',
            'bank_accounts' => 'nullable|array',
            'bank_accounts.*.bank_name' => 'required_with:bank_accounts|string|max:255',
            'bank_accounts.*.account_type' => 'required_with:bank_accounts|string|max:255',
            'bank_accounts.*.account_number' => 'required_with:bank_accounts|string|max:255',
            'bank_accounts.*.balance' => 'required_with:bank_accounts|numeric',
        ]);

        $logoPath = null;
        if ($request->hasFile('logo')) {
            $logoPath = $request->file('logo')->store('client-logos', 'public');
        }

        $data = collect($validated)->except(['service_type_ids', 'logo', 'bank_accounts', 'etrade_password'])->toArray();
        $data['logo_path'] = $logoPath;

        if ($request->filled('etrade_password')) {
            $data['etrade_password'] = \Illuminate\Support\Facades\Crypt::encryptString($request->etrade_password);
        }

        $client = Client::create($data);
        $client->serviceTypes()->sync($request->service_type_ids);

        // Save dynamic bank accounts
        if ($request->has('bank_accounts') && is_array($request->bank_accounts)) {
            foreach ($request->bank_accounts as $bank) {
                $client->bankAccounts()->create($bank);
            }
        }

        // Automate Portal User Creation. The TIN is the client's initial
        // password; they are forced to choose their own on first portal login.
        $user = User::create([
            'name' => $client->company_name,
            'email' => $request->email,
            'password' => Hash::make($client->tin_number),
            'must_change_password' => true,
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
            'trade_license_number' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'tax_center' => 'nullable|string|max:255',
            'legal_structure_id' => 'nullable|exists:legal_structures,id',
            'owner_name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:255',
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users')->ignore($client->user?->id)
            ],
            'etrade_email' => 'nullable|string|max:255',
            'etrade_password' => 'nullable|string',
            'venture' => 'nullable|string|max:255',
            'year_established' => 'nullable|integer',
            'sector' => 'required|string|max:255',
            'service_type_ids' => 'required|array|min:1',
            'service_type_ids.*' => 'exists:service_types,id',
            'branch_id' => 'required|exists:branches,id',
            'assigned_employee_id' => 'nullable|exists:users,id',
            'complexity_score' => 'required|integer|min:1|max:10',
            'status' => 'required|in:Active,Risk,Incomplete',
            'logo' => 'nullable|image|max:10000',
            'bank_accounts' => 'nullable|array',
            'bank_accounts.*.bank_name' => 'required_with:bank_accounts|string|max:255',
            'bank_accounts.*.account_type' => 'required_with:bank_accounts|string|max:255',
            'bank_accounts.*.account_number' => 'required_with:bank_accounts|string|max:255',
            'bank_accounts.*.balance' => 'required_with:bank_accounts|numeric',
        ]);

        if ($request->hasFile('logo')) {
            if ($client->logo_path) {
                Storage::disk('public')->delete($client->logo_path);
            }
            $client->logo_path = $request->file('logo')->store('client-logos', 'public');
            $client->save();
        }

        $data = collect($validated)->except(['service_type_ids', 'logo', 'bank_accounts', 'etrade_password'])->toArray();
        
        if ($request->filled('etrade_password')) {
            $data['etrade_password'] = \Illuminate\Support\Facades\Crypt::encryptString($request->etrade_password);
        }

        $client->update($data);
        $client->serviceTypes()->sync($request->service_type_ids);

        // Sync bank accounts: Clear and recreate
        if ($request->has('bank_accounts') && is_array($request->bank_accounts)) {
            $client->bankAccounts()->delete();
            foreach ($request->bank_accounts as $bank) {
                $client->bankAccounts()->create($bank);
            }
        }

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

    public function revealEtradePassword(Request $request, Client $client)
    {
        $user = auth()->user();
        $isAuthorized = false;

        if ($user->hasRole('Super Admin') || $user->hasRole('Branch Manager')) {
            $isAuthorized = true;
        } elseif ($user->hasRole('Employee') && $client->assigned_employee_id === $user->id) {
            $isAuthorized = true;
        } elseif ($user->hasRole('Client') && $user->client_id === $client->id) {
            $isAuthorized = true;
        }

        if (!$isAuthorized) {
            return response()->json(['error' => 'Unauthorized access.'], 403);
        }

        $request->validate([
            'password' => 'required|string',
        ]);

        if (!Hash::check($request->password, $user->password)) {
            return response()->json(['error' => 'Invalid account password verification.'], 422);
        }

        if (empty($client->etrade_password)) {
            return response()->json(['password' => '']);
        }

        try {
            $decrypted = \Illuminate\Support\Facades\Crypt::decryptString($client->etrade_password);
            return response()->json(['password' => $decrypted]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to decrypt credentials.'], 500);
        }
    }

    public function storeLegalStructure(Request $request)
    {
        abort_unless(auth()->user()->hasAnyRole(['Super Admin', 'Branch Manager']), 403, 'Unauthorized access.');

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:legal_structures,name',
            'description' => 'nullable|string',
        ]);

        $ls = \App\Models\LegalStructure::create($validated);

        return response()->json($ls);
    }

    public function destroy(Client $client)
    {
        abort_unless(auth()->user()->hasAnyRole(['Super Admin']), 403, 'Unauthorized access.');

        $client->delete();

        return back()->with('success', 'Client deleted successfully.');
    }

    public function show(Client $client)
    {
        abort_unless(auth()->user()->hasAnyRole(['Super Admin', 'Branch Manager']), 403, 'Unauthorized access.');

        // Load relations and additional data for the client show page
        $client->load(['branch', 'assignedEmployee', 'serviceTypes', 'user']);

        // 1. Fetch related Tasks
        $tasks = \App\Models\Task::with(['assignedEmployee', 'template'])
            ->where('client_id', $client->id)
            ->latest()
            ->get();

        // 2. Fetch Physical Documents
        $physicalDocuments = \App\Models\FirmDocument::with(['documentType', 'shelfSection.shelf'])
            ->where('client_id', $client->id)
            ->latest()
            ->get();

        // 3. Fetch Direct Message History
        $conversations = \App\Models\Message::with('sender')
            ->where('client_id', $client->id)
            ->oldest()
            ->get();

        // 4. Fetch Project Shared Files
        $projectFiles = \App\Models\TeamProjectFile::with(['project', 'uploader'])
            ->whereHas('project', function ($q) use ($client) {
                $q->where('client_id', $client->id);
            })
            ->latest()
            ->get();

        return Inertia::render('SuperAdmin/ClientShow', [
            'client'            => $client,
            'tasks'             => $tasks,
            'physicalDocuments' => $physicalDocuments,
            'conversations'     => $conversations,
            'projectFiles'      => $projectFiles,
        ]);
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
