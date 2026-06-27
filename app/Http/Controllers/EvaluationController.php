<?php

namespace App\Http\Controllers;

use App\Models\Evaluation;
use App\Models\EvaluationMetric;
use App\Models\EvaluationScore;
use App\Models\StaffEvaluationMetric;
use App\Models\User;
use App\Services\EvaluationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class EvaluationController extends Controller
{
    public function __construct(private readonly EvaluationService $service)
    {
    }

    /**
     * Directory of staff with their latest finalized score for the period.
     */
    public function index(Request $request)
    {
        abort_unless(Auth::user()->can('view evaluations'), 403);

        $month = (int) $request->query('month', now()->month);
        $year  = (int) $request->query('year', now()->year);

        $staffQuery = User::query()
            ->whereHas('staffProfile', fn ($q) => $q->where('is_active', true))
            ->with(['staffProfile', 'branch', 'roles:id,name'])
            ->orderBy('name', 'asc');

        // Only GM (Super Admin) and Operation Manager have firm-wide reach.
        // Every other evaluator (Branch Manager, Team Leader, …) is confined to
        // their own branch — mirrors the Evaluation model's global scope.
        $actor = Auth::user();
        if (! $actor->hasAnyRole(['Super Admin', 'Operation Manager'])) {
            $staffQuery->where('branch_id', $actor->branch_id);
        }

        $staff = $staffQuery->get()->map(function ($u) use ($month, $year) {
            $evaluation = Evaluation::withoutGlobalScopes()
                ->where('user_id', $u->id)
                ->where('period_month', $month)
                ->where('period_year', $year)
                ->first();

            return [
                'id'             => $u->id,
                'name'           => $u->name,
                'avatar'         => $u->profile_photo_url,
                'position'       => $u->staffProfile?->position_title
                    ?? (\App\Models\StaffUser::POSITIONS[$u->staffProfile?->position] ?? '—'),
                'role'           => $u->roles->pluck('name')->first(),
                'branch'         => $u->branch?->name,
                'evaluation_id'  => $evaluation?->id,
                'status'         => $evaluation?->status ?? 'none',
                'overall_score'  => $evaluation?->overall_score,
                'grade'          => $evaluation && $evaluation->overall_score !== null
                    ? EvaluationService::grade((float) $evaluation->overall_score) : null,
            ];
        })->values();

        // Firm-wide period average (finalized only)
        $finalized = $staff->filter(fn ($s) => $s['status'] === 'finalized' && $s['overall_score'] !== null);
        $avg = $finalized->isNotEmpty()
            ? round($finalized->avg(fn ($s) => (float) $s['overall_score']), 1) : null;

        return Inertia::render('SuperAdmin/Evaluations/Index', [
            'staff'   => $staff,
            'filters' => ['month' => $month, 'year' => $year],
            'stats'   => [
                'total'          => $staff->count(),
                'finalized'      => $finalized->count(),
                'pending'        => $staff->count() - $finalized->count(),
                'average_score'  => $avg,
            ],
            'canManageMetrics' => Auth::user()->can('manage evaluation metrics'),
        ]);
    }

    /**
     * The evaluation workspace for one staff member & period.
     */
    public function show(Request $request, User $staff)
    {
        abort_unless(Auth::user()->can('view evaluations'), 403);
        $this->authorizeStaffScope($staff);

        $month = (int) $request->query('month', now()->month);
        $year  = (int) $request->query('year', now()->year);

        $evaluation = $this->service->buildOrGetDraft($staff, $month, $year, Auth::user());
        $evaluation->load(['scores' => fn ($q) => $q->orderBy('id'), 'evaluator:id,name']);

        // The staff's editable rubric (metric assignments + weights)
        $rubric = StaffEvaluationMetric::with('metric')
            ->where('user_id', $staff->id)
            ->orderBy('sort_order')
            ->get()
            ->map(fn ($r) => [
                'id'        => $r->id,
                'metric_id' => $r->evaluation_metric_id,
                'name'      => $r->metric?->name,
                'category'  => $r->metric?->category,
                'category_label' => $r->metric?->category_label,
                'source'    => $r->metric?->source,
                'weight'    => (float) $r->weight,
                'is_active' => $r->is_active,
            ]);

        // Catalogue metrics not yet assigned to this staff (for the add-picker),
        // filtered to those relevant to the staff's role.
        $roleNames = $staff->getRoleNames()->all();
        $assignedIds = $rubric->pluck('metric_id')->all();
        $available = EvaluationMetric::active()
            ->whereNotIn('id', $assignedIds)
            ->orderBy('sort_order')
            ->get()
            ->filter(fn ($m) => $m->appliesToRoles($roleNames))
            ->map(fn ($m) => [
                'id' => $m->id, 'name' => $m->name, 'category' => $m->category,
                'category_label' => $m->category_label, 'source' => $m->source,
                'default_weight' => (float) $m->default_weight,
                'description' => $m->description,
            ])->values();

        return Inertia::render('SuperAdmin/Evaluations/Show', [
            'staff' => [
                'id'       => $staff->id,
                'name'     => $staff->name,
                'avatar'   => $staff->profile_photo_url,
                'email'    => $staff->email,
                'position' => $staff->staffProfile?->position_title
                    ?? (\App\Models\StaffUser::POSITIONS[$staff->staffProfile?->position] ?? '—'),
                'role'     => $staff->getRoleNames()->first(),
                'branch'   => $staff->branch?->name,
            ],
            'evaluation' => [
                'id'            => $evaluation->id,
                'status'        => $evaluation->status,
                'overall_score' => $evaluation->overall_score !== null ? (float) $evaluation->overall_score : null,
                'total_weight'  => $evaluation->total_weight !== null ? (float) $evaluation->total_weight : null,
                'period_month'  => $evaluation->period_month,
                'period_year'   => $evaluation->period_year,
                'period_label'  => $evaluation->period_label,
                'summary_note'  => $evaluation->summary_note,
                'finalized_at'  => $evaluation->finalized_at,
                'evaluator'     => $evaluation->evaluator?->name,
                'scores'        => $evaluation->scores->map(fn (EvaluationScore $s) => [
                    'id'               => $s->id,
                    'metric_id'        => $s->evaluation_metric_id,
                    'metric_name'      => $s->metric_name,
                    'category'         => $s->category,
                    'source'           => $s->source,
                    'is_auto'          => $s->is_auto,
                    'weight'           => (float) $s->weight,
                    'max_score'        => (float) $s->max_score,
                    'raw_score'        => $s->raw_score !== null ? (float) $s->raw_score : null,
                    'normalized_score' => $s->normalized_score !== null ? (float) $s->normalized_score : null,
                    'weighted_score'   => $s->weighted_score !== null ? (float) $s->weighted_score : null,
                    'justification'    => $s->justification,
                    'scored_at'        => $s->scored_at,
                ]),
            ],
            'rubric'    => $rubric,
            'available' => $available,
            'filters'   => ['month' => $month, 'year' => $year],
            'categories' => EvaluationMetric::CATEGORIES,
            'canManageMetrics' => Auth::user()->can('manage evaluation metrics'),
            'canManage'        => Auth::user()->can('manage evaluations'),
        ]);
    }

    /**
     * Save manual scores + justifications on a draft evaluation.
     */
    public function saveScores(Request $request, Evaluation $evaluation)
    {
        abort_unless(Auth::user()->can('manage evaluations'), 403);
        abort_if($evaluation->isFinalized(), 422, 'This evaluation is finalized and locked.');

        $validated = $request->validate([
            'summary_note'            => 'nullable|string|max:2000',
            'scores'                  => 'required|array',
            'scores.*.id'             => 'required|exists:evaluation_scores,id',
            'scores.*.raw_score'      => 'nullable|numeric|min:0',
            'scores.*.justification'  => 'nullable|string|max:1000',
        ]);

        foreach ($validated['scores'] as $row) {
            $line = EvaluationScore::where('evaluation_id', $evaluation->id)->find($row['id']);
            if (! $line) {
                continue;
            }

            // Auto lines keep their computed value; only manual lines accept input.
            if (! $line->is_auto && array_key_exists('raw_score', $row)) {
                $raw = $row['raw_score'];
                $line->raw_score = $raw !== null ? min((float) $raw, (float) $line->max_score) : null;
                $line->normalized_score = $raw !== null
                    ? round(min(100, ((float) $raw / max(1, (float) $line->max_score)) * 100), 2)
                    : null;
                $line->scored_by = Auth::id();
                $line->scored_at = now();
            }

            if (array_key_exists('justification', $row)) {
                $line->justification = $row['justification'];
            }

            $line->save();
        }

        $evaluation->update(['summary_note' => $validated['summary_note'] ?? $evaluation->summary_note]);

        return back()->with('success', 'Evaluation progress saved.');
    }

    public function finalize(Request $request, Evaluation $evaluation)
    {
        abort_unless(Auth::user()->can('manage evaluations'), 403);
        abort_if($evaluation->isFinalized(), 422, 'Already finalized.');

        // Persist any last-minute edits first.
        if ($request->filled('scores')) {
            $this->saveScores($request, $evaluation);
        }

        $this->service->finalize($evaluation, Auth::user());

        return back()->with('success', 'Evaluation finalized and locked.');
    }

    public function reopen(Evaluation $evaluation)
    {
        abort_unless(Auth::user()->hasAnyRole(['Super Admin', 'Operation Manager']), 403);

        $evaluation->update(['status' => 'draft', 'finalized_at' => null]);

        return back()->with('success', 'Evaluation reopened for editing.');
    }

    // ── Rubric (per-staff metric) management ──────────────────────────────────

    public function attachMetric(Request $request, User $staff)
    {
        abort_unless(Auth::user()->can('manage evaluations'), 403);
        $this->authorizeStaffScope($staff);

        $validated = $request->validate([
            'evaluation_metric_id' => 'required|exists:evaluation_metrics,id',
            'weight'               => 'required|numeric|min:0.5|max:100',
        ]);

        $metric = EvaluationMetric::findOrFail($validated['evaluation_metric_id']);

        StaffEvaluationMetric::updateOrCreate(
            ['user_id' => $staff->id, 'evaluation_metric_id' => $metric->id],
            ['weight' => $validated['weight'], 'is_active' => true, 'sort_order' => $metric->sort_order]
        );

        return back()->with('success', "Added “{$metric->name}” to the rubric.");
    }

    public function updateMetricWeight(Request $request, User $staff, StaffEvaluationMetric $assignment)
    {
        abort_unless(Auth::user()->can('manage evaluations'), 403);
        $this->authorizeStaffScope($staff);
        abort_unless($assignment->user_id === $staff->id, 404);

        $validated = $request->validate([
            'weight'    => 'nullable|numeric|min:0|max:100',
            'is_active' => 'nullable|boolean',
        ]);

        $assignment->update(array_filter([
            'weight'    => $validated['weight'] ?? null,
            'is_active' => $request->has('is_active') ? (bool) $validated['is_active'] : null,
        ], fn ($v) => $v !== null));

        return back()->with('success', 'Rubric updated.');
    }

    public function detachMetric(User $staff, StaffEvaluationMetric $assignment)
    {
        abort_unless(Auth::user()->can('manage evaluations'), 403);
        $this->authorizeStaffScope($staff);
        abort_unless($assignment->user_id === $staff->id, 404);

        $assignment->delete();

        return back()->with('success', 'Metric removed from the rubric.');
    }

    /**
     * Guard staff-targeted evaluation actions against horizontal access.
     *
     * The Evaluation model's global scope confines Branch Managers to their
     * branch and everyone else to their own record, but the staff-targeted
     * endpoints below bind a User (and a StaffEvaluationMetric) directly, so the
     * scope never runs. Without this, a Branch Manager or Team Leader holding
     * "manage evaluations" could view or rewrite the rubric of staff in other
     * branches by guessing user IDs. GM / Operation Manager keep firm-wide reach.
     */
    private function authorizeStaffScope(User $staff): void
    {
        /** @var \App\Models\User $actor */
        $actor = Auth::user();

        if ($actor->hasAnyRole(['Super Admin', 'Operation Manager'])) {
            return;
        }

        if ($actor->hasRole('Branch Manager')) {
            abort_unless(
                $staff->branch_id !== null && $staff->branch_id === $actor->branch_id,
                403,
                'You can only manage evaluations for staff in your own branch.'
            );
            return;
        }

        // Any other evaluator (e.g. Team Leader) may only touch their own record.
        abort_unless(
            $staff->id === $actor->id,
            403,
            'You are not allowed to manage this staff member’s evaluation.'
        );
    }

    /**
     * Create a custom global metric (e.g. a firm-specific KPI) that can then be
     * attached to staff. Custom metrics are always manual.
     */
    public function storeMetric(Request $request)
    {
        abort_unless(Auth::user()->can('manage evaluation metrics'), 403);

        $validated = $request->validate([
            'name'             => 'required|string|max:120',
            'description'      => 'nullable|string|max:500',
            'category'         => 'required|string|in:' . implode(',', array_keys(EvaluationMetric::CATEGORIES)),
            'default_weight'   => 'required|numeric|min:0.5|max:100',
            'applies_to_roles' => 'nullable|array',
            'applies_to_roles.*' => 'string',
        ]);

        EvaluationMetric::create([
            'key'              => 'custom_' . \Illuminate\Support\Str::slug($validated['name'], '_') . '_' . substr(uniqid(), -4),
            'name'             => $validated['name'],
            'description'      => $validated['description'] ?? null,
            'category'         => $validated['category'],
            'source'           => 'manual',
            'applies_to_roles' => $validated['applies_to_roles'] ?? null,
            'default_weight'   => $validated['default_weight'],
            'max_score'        => 100,
            'is_system'        => false,
            'is_active'        => true,
            'sort_order'       => 90,
        ]);

        return back()->with('success', 'Custom metric created.');
    }
}
