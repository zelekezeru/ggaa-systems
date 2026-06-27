<?php

namespace App\Http\Controllers;

use App\Models\ServiceType;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class ServiceTypeController extends Controller
{
    public function index()
    {
        abort_unless(auth()->user()->hasRole('Super Admin'), 403);
        
        return Inertia::render('SuperAdmin/ServiceTypes', [
            'serviceTypes' => ServiceType::orderBy('name', 'asc')->get(),
        ]);
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->hasRole('Super Admin'), 403);

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:service_types,name',
            'description' => 'nullable|string',
            'complexity_weight' => 'required|integer|min:1|max:10',
            'is_active' => 'boolean',
        ]);

        $validated['slug'] = Str::slug($validated['name']);

        ServiceType::create($validated);

        return back()->with('success', 'Service type created.');
    }

    public function update(Request $request, ServiceType $serviceType)
    {
        abort_unless(auth()->user()->hasRole('Super Admin'), 403);

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:service_types,name,' . $serviceType->id,
            'description' => 'nullable|string',
            'complexity_weight' => 'required|integer|min:1|max:10',
            'is_active' => 'boolean',
        ]);

        $validated['slug'] = Str::slug($validated['name']);

        $serviceType->update($validated);

        return back()->with('success', 'Service type updated.');
    }

    public function destroy(ServiceType $serviceType)
    {
        abort_unless(auth()->user()->hasRole('Super Admin'), 403);

        if ($serviceType->clients()->count() > 0) {
            return back()->with('error', 'Cannot delete service type assigned to clients.');
        }

        $serviceType->delete();

        return back()->with('success', 'Service type deleted.');
    }
}
