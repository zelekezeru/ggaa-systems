<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Branch;
use App\Models\User;
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

        $branch = Branch::create($validated);
        $this->promoteToBranchManager($branch);

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

        $previousManagerId = $branch->manager_id;
        $branch->update($validated);
        $this->promoteToBranchManager($branch, $previousManagerId);

        return back()->with('success', 'Branch updated successfully.');
    }

    /**
     * Promote the assigned manager to Branch Manager role + bind their branch_id.
     * If a previous manager is being replaced, leave their role alone (they may still
     * be a Branch Manager elsewhere) — but clear branch_id only if they don't manage
     * another branch.
     */
    protected function promoteToBranchManager(Branch $branch, ?int $previousManagerId = null): void
    {
        if ($previousManagerId && $previousManagerId !== $branch->manager_id) {
            $previous = User::find($previousManagerId);
            if ($previous && ! Branch::where('manager_id', $previous->id)->exists()) {
                // No longer manages any branch — drop role + clear branch link
                $previous->removeRole('Branch Manager');
                if ($previous->branch_id === $branch->id) {
                    $previous->update(['branch_id' => null]);
                }
            }
        }

        if ($branch->manager_id) {
            $manager = User::find($branch->manager_id);
            if ($manager) {
                if (! $manager->hasRole('Branch Manager')) {
                    $manager->assignRole('Branch Manager');
                }
                if ($manager->branch_id !== $branch->id) {
                    $manager->update(['branch_id' => $branch->id]);
                }
            }
        }
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