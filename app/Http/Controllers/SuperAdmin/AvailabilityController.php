<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Client;
use App\Models\Task;
use App\Models\User;
use App\Services\CapacityMonitor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class AvailabilityController extends Controller
{
    public function __construct(private readonly CapacityMonitor $monitor)
    {
    }

    /**
     * Team capacity dashboard — answers "Who is free to take more work?"
     * Combines per-employee load, per-branch utilization, and unassigned tasks.
     */
    public function index(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        abort_unless($user->hasAnyRole(['Super Admin', 'Branch Manager']), 403);

        $maxCapacity = (int) config('workforce.max_capacity');

        // Branch filter — Branch Managers are locked to their branch.
        $branchesQuery = Branch::with(['manager:id,name'])->where('is_active', true);
        if ($user->hasRole('Branch Manager') && ! $user->hasRole('Super Admin')) {
            $branchesQuery->where('id', $user->branch_id);
        }
        $branches = $branchesQuery->get();

        $employees = User::role('Employee')
            ->when(
                $user->hasRole('Branch Manager') && ! $user->hasRole('Super Admin'),
                fn ($q) => $q->where('branch_id', $user->branch_id)
            )
            ->with('branch:id,name')
            ->get(['id', 'name', 'branch_id', 'profile_photo_path'])
            ->map(function (User $u) use ($maxCapacity) {
                $load = $u->getCurrentCapacityLoad();
                $remaining = max(0, $maxCapacity - $load);
                $pct = $maxCapacity > 0 ? min(100, (int) round(($load / $maxCapacity) * 100)) : 0;

                return [
                    'id'                => $u->id,
                    'name'              => $u->name,
                    'avatar'            => $u->profile_photo_url,
                    'branch_id'         => $u->branch_id,
                    'branch_name'       => $u->branch?->name,
                    'current_load'      => $load,
                    'remaining'         => $remaining,
                    'capacity_percent'  => $pct,
                    'status'            => $this->statusFor($pct),
                    'monthly_score'     => $u->getWeightedPerformanceScore(now()->month, now()->year),
                ];
            })
            ->sortBy('capacity_percent')
            ->values();

        $branchStats = $branches->map(fn (Branch $b) => array_merge(
            ['id' => $b->id, 'name' => $b->name, 'manager' => $b->manager?->name],
            $this->monitor->branchStats($b)
        ));

        $unassignedTasks = Task::whereNull('assigned_user_id')
            ->where('status', '!=', 'Done')
            ->with(['client:id,company_name,branch_id,complexity_score', 'template:id,name'])
            ->orderBy('due_date')
            ->limit(50)
            ->get()
            ->map(fn (Task $t) => [
                'id'              => $t->id,
                'template_name'   => $t->template?->name,
                'client_name'     => $t->client?->company_name,
                'client_id'       => $t->client_id,
                'branch_id'       => $t->client?->branch_id,
                'complexity'      => $t->client?->complexity_score ?? 1,
                'due_date'        => $t->due_date?->toDateString(),
                'risk_level'      => $t->risk_level,
            ]);

        // Pre-compute per-task suggested employees: who has enough remaining capacity?
        $suggestions = $unassignedTasks->mapWithKeys(function ($task) use ($employees) {
            $needed = $task['complexity'];
            $candidates = $employees
                ->where('remaining', '>=', $needed)
                ->when(
                    $task['branch_id'],
                    fn ($c) => $c->where('branch_id', $task['branch_id'])
                )
                ->sortByDesc('monthly_score')
                ->take(3)
                ->values();

            return [$task['id'] => $candidates];
        });

        return Inertia::render('SuperAdmin/Availability', [
            'employees'       => $employees,
            'branches'        => $branchStats,
            'unassignedTasks' => $unassignedTasks,
            'suggestions'     => $suggestions,
            'maxCapacity'     => $maxCapacity,
            'thresholds'      => config('workforce.capacity_thresholds'),
        ]);
    }

    private function statusFor(int $pct): string
    {
        $thresholds = config('workforce.capacity_thresholds');
        $maxCap = (int) config('workforce.max_capacity');

        // Convert point thresholds to percentages
        $growingPct  = (int) round(($thresholds['growing']  / $maxCap) * 100);
        $balancedPct = (int) round(($thresholds['balanced'] / $maxCap) * 100);

        if ($pct <= $growingPct)  return 'available';   // green
        if ($pct <= $balancedPct) return 'balanced';    // yellow
        return 'overloaded';                             // red
    }
}
