<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Branch;
use App\Models\ServiceType;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;

class StaffController extends Controller
{
    // Display the Staff Page
    public function index()
    {
        abort_unless(auth()->user()->can('view-user'), 403, 'Unauthorized access.');

        $staff = User::role('Employee')
            ->with(['branch', 'serviceTypes'])
            ->withCount('clients')
            ->get()
            ->map(function ($employee) {
                // Attach the dynamic capacity points we built earlier
                $employee->capacity_points = $employee->getCurrentCapacityLoad();
                return $employee;
            });

        // We also need branches to populate the "Assign to Branch" dropdown in the modal
        $branches = Branch::where('is_active', true)->get(['id', 'name']);
        
        // Load active service types for staff specialization
        $serviceTypes = ServiceType::where('is_active', true)->get(['id', 'name']);

        return Inertia::render('SuperAdmin/Staff', [
            'staff' => $staff,
            'branches' => $branches,
            'serviceTypes' => $serviceTypes,
        ]);
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->can('manage-user'), 403, 'Unauthorized access.');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'branch_id' => 'required|exists:branches,id',
            'service_type_ids' => 'nullable|array',
            'service_type_ids.*' => 'exists:service_types,id',
            'profile_photo' => 'nullable|image|max:2048',
        ]);

        $photoPath = null;
        if ($request->hasFile('profile_photo')) {
            $photoPath = $request->file('profile_photo')->store('profile-photos', 'public');
        }

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'branch_id' => $validated['branch_id'],
            'profile_photo_path' => $photoPath,
        ]);

        if ($request->has('service_type_ids')) {
            $user->serviceTypes()->sync($request->service_type_ids);
        }

        // Assign the strict Spatie role
        $user->assignRole('Employee');

        return back()->with('success', 'Employee created successfully.');
    }

    public function update(Request $request, User $staff)
    {
        abort_unless(auth()->user()->can('edit-user'), 403, 'Unauthorized access.');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($staff->id)],
            'branch_id' => 'required|exists:branches,id',
            'service_type_ids' => 'nullable|array',
            'service_type_ids.*' => 'exists:service_types,id',
            'profile_photo' => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('profile_photo')) {
            if ($staff->profile_photo_path) {
                Storage::disk('public')->delete($staff->profile_photo_path);
            }
            $staff->profile_photo_path = $request->file('profile_photo')->store('profile-photos', 'public');
        }

        $staff->name = $validated['name'];
        $staff->email = $validated['email'];
        $staff->branch_id = $validated['branch_id'];
        $staff->save();

        if ($request->has('service_type_ids')) {
            $staff->serviceTypes()->sync($request->service_type_ids);
        }

        return back()->with('success', 'Employee profile updated.');
    }

    public function resetPassword(User $staff)
    {
        abort_unless(auth()->user()->can('manage-user'), 403, 'Unauthorized access.');

        $newPassword = Str::password(12, true, true, false, false);
        
        $staff->password = Hash::make($newPassword);
        $staff->save();

        try {
            Mail::raw("Hello {$staff->name},\n\nYour manager has reset your account password. Your new password is:\n\n{$newPassword}\n\nPlease login and change it immediately.", function ($message) use ($staff) {
                $message->to($staff->email)
                        ->subject('Your New Account Password');
            });
            return back()->with('success', "Password reset! A temporary password has been emailed to {$staff->email}.");
        } catch (\Exception $e) {
            return back()->with('warning', "Email not configured locally. Password reset to: {$newPassword}");
        }
    }

    public function destroy(User $staff)
    {
        abort_unless(auth()->user()->can('delete-user'), 403, 'Unauthorized access.');

        if ($staff->clients()->count() > 0) {
            return back()->with('error', 'Cannot delete an employee with assigned clients. Please reassign their clients first.');
        }

        $staff->delete();

        return back()->with('success', 'Employee removed from the system.');
    }
}