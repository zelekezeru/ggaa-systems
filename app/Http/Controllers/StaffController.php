<?php

namespace App\Http\Controllers;

use App\Mail\StaffBroadcastMail;
use App\Models\Branch;
use App\Models\ServiceType;
use App\Models\StaffUser;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class StaffController extends Controller
{
    /**
     * Map a `staff_users.position` enum value to the Spatie role we should
     * assign to the user. Keep these in sync with RoleAndPermissionSeeder.
     */
    private const POSITION_TO_ROLE = [
        // Executive
        'general_manager'     => 'Super Admin',      // GM holds top authority
        // Management
        'operation_manager'   => 'Operation Manager',
        'finance_admin'       => 'Finance Admin',
        'branch_manager'      => 'Branch Manager',
        // Team leaders
        'team_leader'         => 'Team Leader',
        // Operations staff
        'senior_accountant'   => 'Employee',
        'junior_accountant'   => 'Employee',
        'file_management'     => 'Employee',
        // Finance & Admin support
        'hr_purchase_cashier' => 'Finance Admin',    // cashier sits under Finance & Admin
        'technical_support'   => 'Employee',
        'cleaning'            => 'Employee',

        // ── Legacy keys (kept so historical rows never crash before remap) ──
        'employee'    => 'Employee',
        'manager'     => 'Branch Manager',
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
            'teams'        => StaffUser::TEAMS,
            'positionTiers' => StaffUser::POSITION_TIERS,
        ]);
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->can('manage-user'), 403, 'Unauthorized access.');

        $validated = $request->validate([
            'name'              => 'required|string|max:255',
            'email'             => 'required|string|email|max:255|unique:users',
            'password'          => 'nullable|string|min:8|confirmed',
            'user_type'         => 'required|in:staff,client',
            'branch_id'         => 'required|exists:branches,id',
            'service_type_ids'  => 'nullable|array',
            'service_type_ids.*' => 'exists:service_types,id',
            'profile_photo'     => 'nullable|image|max:10000',

            // Staff-only
            'position'          => 'required_if:user_type,staff|in:' . implode(',', array_keys(StaffUser::POSITIONS)),
            'position_title'    => 'nullable|string|max:255',
            'team'              => 'nullable|in:' . implode(',', array_keys(StaffUser::TEAMS)),
            'employment_type'   => 'nullable|in:full_time,part_time,contract',
            'hire_date'         => 'nullable|date',

            // Client-only
            'client_id'         => 'required_if:user_type,client|nullable|exists:clients,id',
        ]);

        $photoPath = null;
        if ($request->hasFile('profile_photo')) {
            $photoPath = $request->file('profile_photo')->store('profile-photos', 'public');
        }

        // New accounts default to the shared "ggaa@password" credential and are
        // forced to set their own password on first login. An admin may still
        // supply an explicit password, which is likewise change-on-first-login.
        $user = User::create([
            'name'                 => $validated['name'],
            'email'                => $validated['email'],
            'password'             => Hash::make($validated['password'] ?? 'ggaa@password'),
            'must_change_password' => true,
            'branch_id'            => $validated['branch_id'],
            'profile_photo_path'   => $photoPath,
            'client_id'            => $validated['user_type'] === 'client' ? $validated['client_id'] : null,
        ]);

        if ($request->filled('service_type_ids')) {
            $user->serviceTypes()->sync($validated['service_type_ids']);
        }

        if ($validated['user_type'] === 'staff') {
            StaffUser::create([
                'user_id'         => $user->id,
                'position'        => $validated['position'],
                'position_title'  => $validated['position_title'] ?? null,
                'team'            => $validated['team'] ?? null,
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
            'team'                   => 'nullable|in:' . implode(',', array_keys(StaffUser::TEAMS)),
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
                'team'                   => $request->has('team') ? $validated['team'] : $staff->staffProfile->team,
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

        // Send a proper, time-limited password-reset link so the staff member
        // chooses their own password — never email a plaintext credential.
        try {
            $status = Password::sendResetLink(['email' => $staff->email]);

            return $status === Password::RESET_LINK_SENT
                ? back()->with('success', "Password reset link sent to {$staff->email}.")
                : back()->with('error', trans($status));
        } catch (\Exception $e) {
            report($e);
            return back()->with('error', 'Could not send the reset email. Please verify the mail configuration.');
        }
    }

    /**
     * Send a one-off email to a set of staff members selected from the list.
     * Delivered synchronously so it works without a running queue worker.
     */
    public function sendBulkEmail(Request $request)
    {
        abort_unless(auth()->user()->can('manage-user'), 403, 'Unauthorized access.');

        $validated = $request->validate([
            'user_ids'   => ['required', 'array', 'min:1'],
            'user_ids.*' => ['integer', 'exists:users,id'],
            'subject'    => ['required', 'string', 'max:255'],
            'message'    => ['required', 'string', 'max:5000'],
        ]);

        $recipients = User::whereIn('id', $validated['user_ids'])
            ->whereNotNull('email')
            ->get(['id', 'name', 'email']);

        if ($recipients->isEmpty()) {
            return back()->with('error', 'None of the selected users have an email address.');
        }

        $sent = 0;
        $failed = [];

        foreach ($recipients as $recipient) {
            try {
                Mail::to($recipient->email)->send(
                    new StaffBroadcastMail($validated['subject'], $validated['message'], $recipient->name)
                );
                $sent++;
            } catch (\Throwable $e) {
                report($e);
                $failed[] = $recipient->email;
            }
        }

        if (! empty($failed)) {
            return back()->with(
                'warning',
                "Sent to {$sent} of {$recipients->count()}. Failed: " . implode(', ', $failed) . '. Check the mail configuration.'
            );
        }

        return back()->with('success', "Message sent to {$sent} staff member(s).");
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
            'teams' => StaffUser::TEAMS,
            'performance' => [
                'current_month' => $currentMonthPerformance,
                'previous_month' => $prevMonthPerformance,
            ]
        ]);
    }
}
