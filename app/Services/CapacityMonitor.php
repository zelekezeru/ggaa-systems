<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\User;
use App\Notifications\CapacityWarningNotification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;

class CapacityMonitor
{
    /**
     * Computes per-branch utilization and pushes a CapacityWarningNotification
     * to the branch manager and any Super Admins when the configured warning
     * threshold is exceeded.
     *
     * Throttled per-branch: at most one notification every 12 hours.
     */
    public function checkBranchAndNotify(?Branch $branch): void
    {
        if (! $branch) {
            return;
        }

        $stats = $this->branchStats($branch);
        $threshold = (int) config('workforce.branch_capacity_warning_pct', 80);

        if ($stats['utilization_pct'] < $threshold) {
            return;
        }

        // Throttle: skip if a similar notification was sent in the last 12 hours.
        if ($this->recentlyNotified($branch)) {
            return;
        }

        $recipients = collect();
        if ($branch->manager) $recipients->push($branch->manager);

        $superAdmins = User::role('Super Admin')->get();
        $recipients = $recipients->merge($superAdmins)->unique('id');

        if ($recipients->isEmpty()) {
            return;
        }

        Notification::send($recipients, new CapacityWarningNotification(
            $branch,
            $stats['utilization_pct'],
            $stats['employees_at_capacity'],
            $stats['total_employees'],
        ));
    }

    /**
     * Returns utilization stats for a branch.
     *
     * @return array{
     *   total_employees:int,
     *   total_load:int,
     *   max_capacity_total:int,
     *   utilization_pct:int,
     *   employees_at_capacity:int
     * }
     */
    public function branchStats(Branch $branch): array
    {
        $maxPerEmployee = (int) config('workforce.max_capacity');
        $employees = $branch->employees()->role('Employee')->get();

        $total = $employees->count();
        if ($total === 0) {
            return [
                'total_employees'       => 0,
                'total_load'            => 0,
                'max_capacity_total'    => 0,
                'utilization_pct'       => 0,
                'employees_at_capacity' => 0,
            ];
        }

        $totalLoad = $employees->sum(fn (User $u) => $u->getCurrentCapacityLoad());
        $maxTotal  = $total * $maxPerEmployee;
        $util      = $maxTotal > 0 ? (int) round(($totalLoad / $maxTotal) * 100) : 0;

        $atCap = $employees->filter(
            fn (User $u) => $u->getCurrentCapacityLoad() >= $maxPerEmployee * 0.9
        )->count();

        return [
            'total_employees'       => $total,
            'total_load'            => $totalLoad,
            'max_capacity_total'    => $maxTotal,
            'utilization_pct'       => $util,
            'employees_at_capacity' => $atCap,
        ];
    }

    private function recentlyNotified(Branch $branch): bool
    {
        $since = Carbon::now()->subHours(12);

        return \DB::table('notifications')
            ->where('type', CapacityWarningNotification::class)
            ->where('created_at', '>=', $since)
            ->where('data', 'like', '%"branch_id":' . $branch->id . '%')
            ->exists();
    }
}
