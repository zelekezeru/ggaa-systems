<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\ServiceType;
use App\Models\StaffUser;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class StaffController extends Controller
{
    /**
     * Map a `staff_users.position` enum value to the Spatie role we should
     * assign to the user. Keep these in sync with RoleAndPermissionSeeder.
     */
    private const POSITION_TO_ROLE = [
        'employee'    => 'Employee',
        'manager'     => 'Branch Manager',
        'team_leader' => 'Team Leader',
        'admin'       => 'Super Admin',
        'finance'     => 'Finance Admin',
        'other'       => 'Employee',
    ];

    public function index()
    {
        abort_unless(auth()->user()->can('view-user'), 403, 'Unauthorized access.');

        $staff = User::query()
            ->whereHas('staffProfile')
            ->with(['branch', 'serviceTypes', 'staffProfile', 'roles:id,name'])
            ->withCount('clients')
            ->get()
            ->map(function ($employee) {
                $employee->capacity_points = $employee->getCurrentCapacityLoad();
                $employee->position_label  = $employee->staffProfile
                    ? (StaffUser::POSITIONS[$employee->staffProfile->position] ?? null)
                    : null;
                return $employee;
            });

        $branches     = Branch::where('is_active', true)->get(['id', 'name']);
        $serviceTypes = ServiceType::where('is_active', true)->get(['id', 'name']);

        return Inertia::render('SuperAdmin/Staff', [
            'staff'        => $staff,
            'branches'     => $branches,
            'serviceTypes' => $serviceTypes,
            'positions'    => StaffUser::POSITIONS,
        ]);
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->can('manage-user'), 403, 'Unauthorized access.');

        $validated = $request->validate([
            'name'              => 'required|string|max:255',
            'email'             => 'required|string|email|max:255|unique:users',
            'password'          => 'required|string|min:8|confirmed',
            'user_type'         => 'required|in:staff,client',
            'branch_id'         => 'required|exists:branches,id',
            'service_type_ids'  => 'nullable|array',
            'service_type_ids.*' => 'exists:service_types,id',
            'profile_photo'     => 'nullable|image|max:10000',

            // Staff-only
            'position'          => 'required_if:user_type,staff|in:' . implode(',', array_keys(StaffUser::POSITIONS)),
            'position_title'    => 'nullable|string|max:255',
            'employment_type'   => 'nullable|in:full_time,part_time,contract',
            'hire_date'         => 'nullable|date',

            // Client-only
            'client_id'         => 'required_if:user_type,client|nullable|exists:clients,id',
        ]);

        $photoPath = null;
        if ($request->hasFile('profile_photo')) {
            $photoPath = $request->file('profile_photo')->store('profile-photos', 'public');
        }

        $user = User::create([
            'name'               => $validated['name'],
            'email'              => $validated['email'],
            'password'           => Hash::make($validated['password']),
            'branch_id'          => $validated['branch_id'],
            'profile_photo_path' => $photoPath,
            'client_id'          => $validated['user_type'] === 'client' ? $validated['client_id'] : null,
        ]);

        if ($request->filled('service_type_ids')) {
            $user->serviceTypes()->sync($validated['service_type_ids']);
        }

        if ($validated['user_type'] === 'staff') {
            StaffUser::create([
                'user_id'         => $user->id,
                'position'        => $validated['position'],
                'position_title'  => $validated['position_title'] ?? null,
                'employment_type' => $validated['employment_type'] ?? 'full_time',
                'hire_date'       => $validated['hire_date'] ?? null,
                'is_active'       => true,
            ]);

            $role = self::POSITION_TO_ROLE[$validated['position']] ?? 'Employee';
            $user->assignRole($role);

            return back()->with('success', "Staff member created as {$role}.");
        }

        $user->assignRole('Client');

        return back()->with('success', 'Client portal user created.');
    }

    public function update(Request $request, User $staff)
    {
        abort_unless(auth()->user()->can('edit-user'), 403, 'Unauthorized access.');

        $validated = $request->validate([
            'name'                   => 'required|string|max:255',
            'email'                  => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($staff->id)],
            'branch_id'              => 'required|exists:branches,id',
            'service_type_ids'       => 'nullable|array',
            'service_type_ids.*'     => 'exists:service_types,id',
            'profile_photo'          => 'nullable|image|max:10000',
            'position'               => 'nullable|in:' . implode(',', array_keys(StaffUser::POSITIONS)),
            'position_title'         => 'nullable|string|max:255',
            'employment_type'        => 'nullable|in:full_time,part_time,contract',
            'is_active'              => 'nullable|boolean',
            'max_capacity'           => 'nullable|integer|min:1|max:100',
            'academic_experience'    => 'nullable|string',
            'training_experience'    => 'nullable|string',
            'performance_experience' => 'nullable|string',
        ]);

        if ($request->hasFile('profile_photo')) {
            if ($staff->profile_photo_path) {
                Storage::disk('public')->delete($staff->profile_photo_path);
            }
            $staff->profile_photo_path = $request->file('profile_photo')->store('profile-photos', 'public');
        }

        $staff->name      = $validated['name'];
        $staff->email     = $validated['email'];
        $staff->branch_id = $validated['branch_id'];
        $staff->save();

        if ($request->has('service_type_ids')) {
            $staff->serviceTypes()->sync($request->input('service_type_ids', []));
        }

        if ($staff->staffProfile) {
            $oldRole = self::POSITION_TO_ROLE[$staff->staffProfile->position] ?? null;
            $newRole = self::POSITION_TO_ROLE[$validated['position'] ?? $staff->staffProfile->position] ?? 'Employee';

            $staff->staffProfile->update([
                'position'               => $validated['position'] ?? $staff->staffProfile->position,
                'position_title'         => $validated['position_title'] ?? $staff->staffProfile->position_title,
                'employment_type'        => $validated['employment_type'] ?? $staff->staffProfile->employment_type,
                'is_active'              => $validated['is_active'] ?? $staff->staffProfile->is_active,
                'max_capacity'           => $validated['max_capacity'] ?? $staff->staffProfile->max_capacity,
                'academic_experience'    => $validated['academic_experience'] ?? $staff->staffProfile->academic_experience,
                'training_experience'    => $validated['training_experience'] ?? $staff->staffProfile->training_experience,
                'performance_experience' => $validated['performance_experience'] ?? $staff->staffProfile->performance_experience,
            ]);

            if ($oldRole !== $newRole) {
                if ($oldRole) {
                    $staff->removeRole($oldRole);
                }
                $staff->assignRole($newRole);
            }
        }

        return back()->with('success', 'Staff profile updated.');
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
            return back()->with('error', 'Cannot delete a staff member with assigned clients. Please reassign their clients first.');
        }

        $staff->delete();

        return back()->with('success', 'Staff member removed from the system.');
    }

    public function show(User $staff)
    {
        abort_unless(auth()->user()->hasAnyRole(['Super Admin', 'Branch Manager']), 403, 'Unauthorized access.');

        $staff->load(['branch', 'serviceTypes', 'staffProfile', 'roles']);

        // Current workload metrics
        $staff->capacity_points = $staff->getCurrentCapacityLoad();
        $staff->max_capacity_limit = $staff->staffProfile->max_capacity ?? (int) config('workforce.max_capacity');
        $staff->capacity_percent = $staff->capacity_percent;
        $staff->remaining_capacity = $staff->remaining_capacity;

        // Fetch performance score for current and previous month
        $currentMonthPerformance = $staff->getWeightedPerformanceScore(now()->month, now()->year);
        $prevMonthPerformance = $staff->getWeightedPerformanceScore(now()->subMonth()->month, now()->subMonth()->year);

        // Fetch assigned Tasks
        $tasks = \App\Models\Task::with(['client', 'template'])
            ->where('assigned_user_id', $staff->id)
            ->latest()
            ->get();

        $branches = Branch::where('is_active', true)->get(['id', 'name']);
        $serviceTypes = ServiceType::where('is_active', true)->get(['id', 'name']);

        return Inertia::render('SuperAdmin/StaffShow', [
            'staff' => $staff,
            'tasks' => $tasks,
            'branches' => $branches,
            'serviceTypes' => $serviceTypes,
            'positions' => StaffUser::POSITIONS,
            'performance' => [
                'current_month' => $currentMonthPerformance,
                'previous_month' => $prevMonthPerformance,
            ]
        ]);
    }
}
