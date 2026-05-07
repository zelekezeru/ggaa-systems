<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Client;
use App\Models\MonthlyLedger;
use App\Models\Task;
use App\Models\TeamProject;
use App\Models\User;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        // ── KPI counts ──────────────────────────────────────────────────────
        $totalClients  = Client::withoutGlobalScopes()->count();
        $activeClients = Client::withoutGlobalScopes()->where('status', 'Active')->count();

        $taskCounts = Task::withoutGlobalScopes()
            ->selectRaw("
                COUNT(*) as total,
                SUM(status = 'Done') as done,
                SUM(status = 'In Review') as in_review,
                SUM(status = 'To Do') as todo,
                SUM(status = 'Waiting on Client') as waiting,
                SUM(due_date < NOW() AND status != 'Done') as overdue
            ")->first();

        $totalStaff = User::role('Employee')->count();

        // ── Ledger snapshot ─────────────────────────────────────────────────
        $ledgerStats = MonthlyLedger::withoutGlobalScopes()
            ->selectRaw("
                COUNT(*) as total,
                SUM(status = 'draft') as draft,
                SUM(status = 'submitted') as submitted,
                SUM(status = 'verified') as verified,
                SUM(CASE WHEN status = 'verified' THEN
                    COALESCE(cash_machine_sales,0) + COALESCE(manual_sales,0)
                ELSE 0 END) as total_verified_sales
            ")->first();

        // Ledger entries needing review (submitted but not yet verified)
        $pendingLedgers = MonthlyLedger::withoutGlobalScopes()
            ->where('status', 'submitted')
            ->with('client:id,company_name')
            ->latest('updated_at')
            ->limit(5)
            ->get(['id', 'client_id', 'eth_year', 'eth_month', 'status', 'updated_at']);

        // ── Branch health ────────────────────────────────────────────────────
        $branches = Branch::withoutGlobalScopes()
            ->with(['clients' => fn ($q) => $q->withoutGlobalScopes()->with(['tasks' => fn ($t) => $t->withoutGlobalScopes()])])
            ->withCount([
                'clients as client_count' => fn ($q) => $q->withoutGlobalScopes(),
                'employees as staff_count',
            ])
            ->get()
            ->map(function ($branch) {
                $allTasks   = $branch->clients->flatMap->tasks;
                $total      = $allTasks->count();
                $done       = $allTasks->where('status', 'Done')->count();
                $overdue    = $allTasks->filter(fn ($t) => $t->due_date && $t->due_date->isPast() && $t->status !== 'Done')->count();
                return [
                    'id'              => $branch->id,
                    'name'            => $branch->name,
                    'client_count'    => $branch->client_count,
                    'staff_count'     => $branch->staff_count,
                    'compliance_rate' => $total > 0 ? (int) round(($done / $total) * 100) : 100,
                    'overdue_tasks'   => $overdue,
                    'total_tasks'     => $total,
                ];
            });

        // ── Staff capacity ───────────────────────────────────────────────────
        $employees = User::role('Employee')
            ->with('branch:id,name')
            ->withCount('clients')
            ->get()
            ->map(fn ($e) => [
                'id'              => $e->id,
                'name'            => $e->name,
                'branch'          => ['name' => $e->branch?->name ?? '—'],
                'clients_count'   => $e->clients_count,
                'capacity_points' => $e->getCurrentCapacityLoad(),
            ])
            ->sortByDesc('capacity_points')
            ->values();

        // ── Team projects snapshot ───────────────────────────────────────────
        $projectStats = [
            'total'       => TeamProject::count(),
            'in_progress' => TeamProject::where('status', 'in_progress')->count(),
            'in_review'   => TeamProject::where('status', 'in_review')->count(),
            'overdue'     => TeamProject::whereIn('status', ['in_progress', 'planning', 'in_review'])
                                ->whereDate('due_date', '<', now())->count(),
        ];

        // ── Recent urgent tasks (overdue, not done, sorted by weighted risk) ─
        $urgentTasks = Task::withoutGlobalScopes()
            ->with(['client:id,company_name', 'assignedEmployee:id,name', 'template:id,name'])
            ->where('status', '!=', 'Done')
            ->whereNotNull('due_date')
            ->where('due_date', '<', now())
            ->orderBy('due_date')
            ->limit(8)
            ->get(['id', 'task_template_id', 'status', 'due_date', 'client_id', 'assigned_user_id'])
            ->map(function ($task) {
                $task->title = $task->template?->name ?? 'Task #' . $task->id;
                return $task;
            });

        // ── Task status breakdown for mini chart ────────────────────────────
        $taskBreakdown = [
            ['label' => 'Done',              'count' => (int) $taskCounts->done,      'color' => '#10b981'],
            ['label' => 'In Review',         'count' => (int) $taskCounts->in_review, 'color' => '#3b82f6'],
            ['label' => 'To Do',             'count' => (int) $taskCounts->todo,      'color' => '#f59e0b'],
            ['label' => 'Waiting on Client', 'count' => (int) $taskCounts->waiting,   'color' => '#f43f5e'],
        ];

        return Inertia::render('SuperAdmin/Dashboard', [
            'kpis' => [
                'total_clients'    => $totalClients,
                'active_clients'   => $activeClients,
                'total_staff'      => $totalStaff,
                'tasks_total'      => (int) $taskCounts->total,
                'tasks_done'       => (int) $taskCounts->done,
                'tasks_overdue'    => (int) $taskCounts->overdue,
                'tasks_waiting'    => (int) $taskCounts->waiting,
                'ledger_pending'   => (int) $ledgerStats->submitted,
                'ledger_verified'  => (int) $ledgerStats->verified,
                'ledger_sales_etb' => (float) $ledgerStats->total_verified_sales,
                'projects_active'  => $projectStats['in_progress'] + $projectStats['in_review'],
                'projects_overdue' => $projectStats['overdue'],
            ],
            'branches'        => $branches,
            'employees'       => $employees,
            'taskBreakdown'   => $taskBreakdown,
            'urgentTasks'     => $urgentTasks,
            'pendingLedgers'  => $pendingLedgers,
        ]);
    }
}
