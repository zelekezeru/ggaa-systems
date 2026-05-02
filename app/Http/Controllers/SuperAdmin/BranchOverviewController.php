<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Task;
use App\Models\User;
use App\Services\CapacityMonitor;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class BranchOverviewController extends Controller
{
    public function __construct(private readonly CapacityMonitor $monitor)
    {
    }

    /**
     * Detailed view of a single branch — its team, clients, tasks, and capacity.
     * Branch Managers can only view their own branch.
     */
    public function show(Branch $branch)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if ($user->hasRole('Branch Manager') && ! $user->hasRole('Super Admin')) {
            abort_unless($branch->id === $user->branch_id, 403);
        } else {
            abort_unless($user->hasRole('Super Admin'), 403);
        }

        $maxCapacity = (int) config('workforce.max_capacity');

        $branch->load('manager:id,name,email');

        $employees = $branch->employees()
            ->role('Employee')
            ->get(['id', 'name', 'email', 'branch_id', 'profile_photo_path'])
            ->map(function (User $u) use ($maxCapacity) {
                $load = $u->getCurrentCapacityLoad();
                $pct  = $maxCapacity > 0 ? min(100, (int) round(($load / $maxCapacity) * 100)) : 0;

                return [
                    'id'               => $u->id,
                    'name'             => $u->name,
                    'email'            => $u->email,
                    'avatar'           => $u->profile_photo_url,
                    'current_load'     => $load,
                    'capacity_percent' => $pct,
                    'remaining'        => max(0, $maxCapacity - $load),
                    'monthly_score'    => $u->getWeightedPerformanceScore(now()->month, now()->year),
                    'task_count'       => Task::where('assigned_user_id', $u->id)
                        ->where('status', '!=', 'Done')
                        ->withoutGlobalScopes()
                        ->count(),
                ];
            })
            ->sortBy('capacity_percent')
            ->values();

        $clients = $branch->clients()
            ->withCount(['tasks as open_tasks' => fn ($q) => $q->where('status', '!=', 'Done')])
            ->get();

        $tasks = Task::whereHas('client', fn ($q) => $q->where('branch_id', $branch->id))
            ->with(['client:id,company_name', 'template:id,name', 'assignedEmployee:id,name'])
            ->orderBy('due_date')
            ->limit(100)
            ->get();

        $stats = $this->monitor->branchStats($branch);
        $stats['unassigned_tasks'] = Task::whereHas('client', fn ($q) => $q->where('branch_id', $branch->id))
            ->whereNull('assigned_user_id')
            ->where('status', '!=', 'Done')
            ->count();
        $stats['overdue_tasks'] = Task::whereHas('client', fn ($q) => $q->where('branch_id', $branch->id))
            ->where('status', '!=', 'Done')
            ->where('due_date', '<', now())
            ->count();

        return Inertia::render('SuperAdmin/BranchDetail', [
            'branch'    => [
                'id'        => $branch->id,
                'name'      => $branch->name,
                'location'  => $branch->location,
                'is_active' => $branch->is_active,
                'manager'   => $branch->manager,
            ],
            'employees' => $employees,
            'clients'   => $clients,
            'tasks'     => $tasks,
            'stats'     => $stats,
            'maxCapacity' => $maxCapacity,
        ]);
    }
}
