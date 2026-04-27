<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\Branch;
use App\Models\User;
use App\Models\Task;
use App\Models\Client;

class SuperAdminController extends Controller
{
    // 1. Branch Management
    public function branches()
    {
        abort_unless(auth()->user()->can('view-branch'), 403, 'Unauthorized access.');

        $branches = Branch::with('manager')
            ->withCount([
                'clients', 
                'employees as staff_count',
                'tasks as total_tasks_count',
                'tasks as completed_tasks_count' => function ($query) {
                    $query->where('tasks.status', 'Done');
                }
            ])
            ->get()
            ->map(function ($branch) {
                // Calculate compliance dynamically based on task completion
                $branch->compliance_rate = $branch->total_tasks_count > 0 
                    ? (int) round(($branch->completed_tasks_count / $branch->total_tasks_count) * 100) 
                    : 100; // 100% health if branch has no active burden

                return $branch;
            });

        return Inertia::render('SuperAdmin/Branches', ['branches' => $branches]);
    }

    // 2. Staff Management
    public function staff()
    {
        $staff = User::role('Employee')
            ->with('branch')
            ->withCount('clients')
            ->get()
            ->map(function ($employee) {
                $employee->capacity_points = $employee->getCurrentCapacityLoad();
                $employee->compliance_score = $employee->getWeightedPerformanceScore(date('m'), date('Y'));
                return $employee;
            });

        return Inertia::render('SuperAdmin/Staff', ['staff' => $staff]);
    }

    // 3. Global Reports
    public function reports()
    {
        $stats = [
            'total_active_clients'  => Client::where('status', 'Active')->count(),
            'tasks_completed_mtd'   => Task::where('status', 'Done')
                                           ->whereMonth('updated_at', date('m'))
                                           ->whereYear('updated_at', date('Y'))
                                           ->count(),
            'tasks_waiting_client'  => Task::where('status', 'Waiting on Client')->count(),
        ];

        return Inertia::render('SuperAdmin/Reports', ['stats' => $stats]);
    }
}