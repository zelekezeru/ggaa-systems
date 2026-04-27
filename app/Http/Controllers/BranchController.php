<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Branch;
use Illuminate\Validation\Rule;

class BranchController extends Controller
{
    public function store(Request $request)
    {
        abort_unless(auth()->user()->can('manage-branch'), 403, 'Unauthorized access.');

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:branches,name',
            'location' => 'required|string|max:255',
            'manager_id' => 'nullable|exists:users,id',
            'is_active' => 'boolean',
        ]);

        Branch::create($validated);

        // The ->with() method attaches a flash message to the session
        return back()->with('success', 'Branch created successfully.');
    }

    public function update(Request $request, Branch $branch)
    {
        abort_unless(auth()->user()->can('edit-branch'), 403, 'Unauthorized access.');

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('branches')->ignore($branch->id)
            ],
            'location' => 'required|string|max:255',
            'manager_id' => 'nullable|exists:users,id',
            'is_active' => 'boolean',
        ]);

        $branch->update($validated);

        return back()->with('success', 'Branch updated successfully.');
    }

    public function destroy(Branch $branch)
    {
        abort_unless(auth()->user()->can('delete-branch'), 403, 'Unauthorized access.');

        // Optional safety check: Prevent deleting branches that have active clients/staff
        if ($branch->clients()->count() > 0 || $branch->employees()->count() > 0) {
            return back()->with('error', 'Cannot delete a branch that has active staff or clients. Please reassign them first.');
        }

        $branch->delete();

        return back()->with('success', 'Branch deleted successfully.');
    }
}