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

class AdminClientController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(auth()->user()->hasAnyRole(['Super Admin', 'Branch Manager']), 403, 'Unauthorized access.');

        $query = Client::with(['branch', 'assignedEmployee', 'serviceTypes']);

        // Filtering by service type
        if ($request->has('service_type')) {
            $query->whereHas('serviceTypes', function($q) use ($request) {
                $q->where('slug', $request->service_type);
            });
        }

        $clients = $query->get();
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
            'sector' => 'required|string|max:255',
            'service_type_ids' => 'required|array|min:1',
            'service_type_ids.*' => 'exists:service_types,id',
            'branch_id' => 'required|exists:branches,id',
            'assigned_employee_id' => 'nullable|exists:users,id',
            'complexity_score' => 'required|integer|min:1|max:10',
            'status' => 'required|in:Active,Risk,Incomplete',
            'logo' => 'nullable|image|max:2048',
        ]);

        $logoPath = null;
        if ($request->hasFile('logo')) {
            $logoPath = $request->file('logo')->store('client-logos', 'public');
        }

        $data = collect($validated)->except(['service_type_ids', 'logo'])->toArray();
        $data['logo_path'] = $logoPath;
        $client = Client::create($data);
        $client->serviceTypes()->sync($request->service_type_ids);

        return back()->with('success', 'Client created successfully.');
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
            'sector' => 'required|string|max:255',
            'service_type_ids' => 'required|array|min:1',
            'service_type_ids.*' => 'exists:service_types,id',
            'branch_id' => 'required|exists:branches,id',
            'assigned_employee_id' => 'nullable|exists:users,id',
            'complexity_score' => 'required|integer|min:1|max:10',
            'status' => 'required|in:Active,Risk,Incomplete',
            'logo' => 'nullable|image|max:2048',
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

        return back()->with('success', 'Client updated successfully.');
    }

    public function destroy(Client $client)
    {
        abort_unless(auth()->user()->hasAnyRole(['Super Admin']), 403, 'Unauthorized access.');

        $client->delete();

        return back()->with('success', 'Client deleted successfully.');
    }
}
