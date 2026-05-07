<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\Branch;
use App\Models\User;
use App\Models\Task;
use App\Models\Client;
use App\Models\TeamProject;
use App\Models\ServiceInvoice;
use App\Models\ServiceInvoicePayment;

class SuperAdminController extends Controller
{
    // 1. Branch Management
    public function branches(): \Inertia\Response
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

        // Eligible managers: existing Branch Managers + Employees who can be promoted
        $managers = User::role(['Branch Manager', 'Employee'])
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'branch_id'])
            ->map(function ($u) {
                $u->roles_label = $u->getRoleNames()->implode(', ');
                return $u;
            });

        return Inertia::render('SuperAdmin/Branches', [
            'branches' => $branches,
            'managers' => $managers,
        ]);
    }

    // 2. Staff Management
    public function staff(): \Inertia\Response
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
    public function reports(): \Inertia\Response
    {
        $month = (int) request()->query('month', date('m'));
        $year  = (int) request()->query('year',  date('Y'));

        // Guard valid ranges
        $month = max(1, min(12, $month));
        $year  = max(2020, min((int) date('Y'), $year));

        // KPI counts
        $stats = [
            'total_active_clients'  => Client::where('status', 'Active')->count(),
            'tasks_completed_mtd'   => Task::where('status', 'Done')
                                           ->whereMonth('updated_at', $month)
                                           ->whereYear('updated_at', $year)
                                           ->count(),
            'tasks_waiting_client'  => Task::where('status', 'Waiting on Client')->count(),
        ];

        // Task status breakdown (all time for the selected month/year)
        $taskBreakdown = [
            ['label' => 'Done',              'count' => Task::where('status', 'Done')->whereMonth('created_at', $month)->whereYear('created_at', $year)->count(),              'color' => 'bg-emerald-500'],
            ['label' => 'In Review',         'count' => Task::where('status', 'In Review')->whereMonth('created_at', $month)->whereYear('created_at', $year)->count(),         'color' => 'bg-indigo-500'],
            ['label' => 'To Do',             'count' => Task::where('status', 'To Do')->whereMonth('created_at', $month)->whereYear('created_at', $year)->count(),             'color' => 'bg-amber-500'],
            ['label' => 'Waiting on Client', 'count' => Task::where('status', 'Waiting on Client')->whereMonth('created_at', $month)->whereYear('created_at', $year)->count(), 'color' => 'bg-rose-500'],
        ];

        // Project breakdown
        $projectBreakdown = [
            ['label' => 'Planning', 'count' => TeamProject::where('status', 'planning')->whereMonth('created_at', $month)->whereYear('created_at', $year)->count()],
            ['label' => 'In Progress', 'count' => TeamProject::where('status', 'in_progress')->whereMonth('created_at', $month)->whereYear('created_at', $year)->count()],
            ['label' => 'In Review', 'count' => TeamProject::where('status', 'in_review')->whereMonth('created_at', $month)->whereYear('created_at', $year)->count()],
            ['label' => 'Completed', 'count' => TeamProject::where('status', 'completed')->whereMonth('created_at', $month)->whereYear('created_at', $year)->count()],
        ];

        // Finance Metrics (MTD)
        $finance = [
            'invoiced_mtd' => ServiceInvoice::whereMonth('created_at', $month)->whereYear('created_at', $year)->where('status', '!=', 'cancelled')->sum('amount'),
            'collected_mtd' => ServiceInvoicePayment::whereMonth('paid_at', $month)->whereYear('paid_at', $year)->where('status', 'Completed')->sum('amount'),
            'outstanding_total' => ServiceInvoice::whereIn('status', ['sent', 'partially_paid', 'overdue'])->sum('amount') - ServiceInvoicePayment::whereHas('invoice', fn($q) => $q->whereIn('status', ['sent', 'partially_paid', 'overdue']))->where('status', 'Completed')->sum('amount')
        ];

        // Employee performance leaderboard
        $employees = User::role('Employee')
            ->with('branch')
            ->withCount('clients')
            ->get()
            ->map(fn ($employee) => [
                'id'             => $employee->id,
                'name'           => $employee->name,
                'branch'         => ['name' => $employee->branch?->name ?? '—'],
                'clients_count'  => $employee->clients_count,
                'capacity_points'=> $employee->getCurrentCapacityLoad(),
                'score'          => $employee->getWeightedPerformanceScore($month, $year),
            ])
            ->sortByDesc('score')
            ->values();

        // Branch compliance snapshot
        $branches = Branch::with(['clients.tasks'])->get()->map(function ($branch) {
            $allTasks   = $branch->clients->flatMap->tasks;
            $total      = $allTasks->count();
            $done       = $allTasks->where('status', 'Done')->count();
            return [
                'id'              => $branch->id,
                'name'            => $branch->name,
                'compliance_rate' => $total > 0 ? round(($done / $total) * 100) : 100,
            ];
        });

        return Inertia::render('SuperAdmin/Reports', [
            'stats'            => $stats,
            'finance'          => $finance,
            'employees'        => $employees,
            'taskBreakdown'    => $taskBreakdown,
            'projectBreakdown' => $projectBreakdown,
            'branches'         => $branches,
            'filters'          => ['month' => $month, 'year' => $year],
        ]);
    }
}