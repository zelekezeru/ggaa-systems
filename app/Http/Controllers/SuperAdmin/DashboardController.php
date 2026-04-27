<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\User;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $branches = Branch::with(['clients.tasks'])->get()->map(function ($branch) {
            $allTasks    = $branch->clients->flatMap->tasks;
            $totalTasks  = $allTasks->count();
            $doneTasks   = $allTasks->where('status', 'Done')->count();

            return [
                'id'              => $branch->id,
                'name'            => $branch->name,
                'compliance_rate' => $totalTasks > 0
                    ? round(($doneTasks / $totalTasks) * 100)
                    : 100,
            ];
        });

        $employees = User::role('Employee')
            ->with('branch')
            ->withCount('clients')
            ->get()
            ->map(fn($employee) => [
                'id'              => $employee->id,
                'name'            => $employee->name,
                'branch'          => ['name' => $employee->branch?->name ?? '—'],
                'clients_count'   => $employee->clients_count,
                'capacity_points' => $employee->getCurrentCapacityLoad(),
            ]);

        return Inertia::render('SuperAdmin/Dashboard', [
            'branches'  => $branches,
            'employees' => $employees,
        ]);
    }
}
