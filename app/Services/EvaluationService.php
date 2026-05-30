<?php

namespace App\Services;

use App\Models\DailyTask;
use App\Models\Evaluation;
use App\Models\EvaluationMetric;
use App\Models\EvaluationScore;
use App\Models\StaffEvaluationMetric;
use App\Models\Task;
use App\Models\TeamProjectTodo;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Builds, scores and finalizes staff performance evaluations.
 *
 * Scoring model (traceable & justifiable):
 *   - Every staff member carries a set of weighted metrics (their rubric).
 *   - Each metric yields a normalized 0-100 score (auto-computed or entered).
 *   - The overall score is the weight-normalized average:
 *         overall = Σ(normalized_i × weight_i) / Σ(weight_i)
 *     so weights never have to sum to exactly 100 — they're normalized.
 */
class EvaluationService
{
    /**
     * Ensure a staff member has a rubric. If they have none yet, provision the
     * active catalogue metrics relevant to their role(s) using default weights.
     */
    public function ensureRubric(User $staff): Collection
    {
        $existing = StaffEvaluationMetric::where('user_id', $staff->id)->get();

        if ($existing->isNotEmpty()) {
            return $existing;
        }

        return $this->provisionDefaults($staff);
    }

    /**
     * Attach every active, role-relevant metric to the staff member.
     */
    public function provisionDefaults(User $staff): Collection
    {
        $roleNames = $staff->getRoleNames()->all();

        $metrics = EvaluationMetric::active()->orderBy('sort_order')->get()
            ->filter(fn (EvaluationMetric $m) => $m->appliesToRoles($roleNames));

        foreach ($metrics as $metric) {
            StaffEvaluationMetric::firstOrCreate(
                ['user_id' => $staff->id, 'evaluation_metric_id' => $metric->id],
                ['weight' => $metric->default_weight, 'is_active' => true, 'sort_order' => $metric->sort_order]
            );
        }

        return StaffEvaluationMetric::where('user_id', $staff->id)->get();
    }

    /**
     * Get (or create as a draft) the evaluation for a staff member & period,
     * syncing its line items to the staff member's current rubric and
     * (re)computing any auto metrics.
     */
    public function buildOrGetDraft(User $staff, int $month, int $year, ?User $evaluator = null): Evaluation
    {
        $this->ensureRubric($staff);

        $evaluation = Evaluation::firstOrCreate(
            ['user_id' => $staff->id, 'period_month' => $month, 'period_year' => $year],
            [
                'evaluator_id' => $evaluator?->id,
                'branch_id'    => $staff->branch_id,
                'status'       => 'draft',
            ]
        );

        if ($evaluation->isFinalized()) {
            return $evaluation->load('scores.metric');
        }

        $this->syncScoreLines($evaluation, $staff, $month, $year, $evaluator);

        return $evaluation->load('scores.metric');
    }

    /**
     * Create/refresh one score line per active rubric metric. Auto metrics are
     * (re)computed; manual metrics keep any value the evaluator already entered.
     */
    protected function syncScoreLines(Evaluation $evaluation, User $staff, int $month, int $year, ?User $evaluator): void
    {
        $rubric = StaffEvaluationMetric::with('metric')
            ->where('user_id', $staff->id)
            ->where('is_active', true)
            ->get()
            ->filter(fn ($r) => $r->metric && $r->metric->is_active);

        $keepMetricIds = [];

        foreach ($rubric as $assignment) {
            $metric = $assignment->metric;
            $keepMetricIds[] = $metric->id;

            $line = EvaluationScore::firstOrNew([
                'evaluation_id'        => $evaluation->id,
                'evaluation_metric_id' => $metric->id,
            ]);

            // Refresh snapshot fields (safe while draft)
            $line->metric_name = $metric->name;
            $line->category    = $metric->category;
            $line->source      = $metric->source;
            $line->weight      = $assignment->weight;
            $line->max_score   = $metric->max_score;
            $line->is_auto     = $metric->source === 'auto';

            if ($metric->source === 'auto') {
                $auto = $this->computeAuto($metric->computation_key, $staff, $month, $year);
                if ($auto !== null) {
                    $line->raw_score = round($auto, 2);
                    $line->normalized_score = round(min(100, ($auto / max(1, (float) $metric->max_score)) * 100), 2);
                    $line->justification = $line->justification ?: 'Auto-computed from system activity for the period.';
                    $line->scored_by = $line->scored_by ?: $evaluator?->id;
                    $line->scored_at = now();
                }
            }

            $line->save();
        }

        // Drop lines for metrics no longer in the rubric (draft only).
        EvaluationScore::where('evaluation_id', $evaluation->id)
            ->when($keepMetricIds, fn ($q) => $q->whereNotIn('evaluation_metric_id', $keepMetricIds))
            ->delete();
    }

    /**
     * Dispatch table for auto-computed metrics. Returns a 0..max_score value
     * or null if not applicable. Add new auto metrics by adding a case here.
     */
    public function computeAuto(?string $key, User $staff, int $month, int $year): ?float
    {
        return match ($key) {
            'task_weighted_score'      => (float) $staff->getWeightedPerformanceScore($month, $year),
            'task_on_time_rate'        => $this->taskOnTimeRate($staff, $month, $year),
            'task_volume_score'        => $this->taskVolumeScore($staff, $month, $year),
            'daily_task_completion'    => $this->dailyTaskCompletion($staff, $month, $year),
            'team_project_contribution' => $this->teamProjectContribution($staff, $month, $year),
            default                    => null,
        };
    }

    // ── Auto calculators ───────────────────────────────────────────────────

    /** % of the staff's completed tasks (due in period) delivered on time. */
    protected function taskOnTimeRate(User $staff, int $month, int $year): float
    {
        $done = Task::withoutGlobalScopes()
            ->where('assigned_user_id', $staff->id)
            ->where('status', 'Done')
            ->whereMonth('due_date', $month)
            ->whereYear('due_date', $year)
            ->whereNotNull('completed_at')
            ->get(['completed_at', 'due_date']);

        if ($done->isEmpty()) {
            return 100.0; // nothing due / all clear
        }

        $onTime = $done->filter(fn ($t) => $t->completed_at <= $t->due_date)->count();

        return round(($onTime / $done->count()) * 100, 2);
    }

    /** Volume score: completed tasks vs a sensible monthly target (8). */
    protected function taskVolumeScore(User $staff, int $month, int $year, int $target = 8): float
    {
        $count = Task::withoutGlobalScopes()
            ->where('assigned_user_id', $staff->id)
            ->where('status', 'Done')
            ->whereMonth('completed_at', $month)
            ->whereYear('completed_at', $year)
            ->count();

        return round(min(100, ($count / max(1, $target)) * 100), 2);
    }

    /** % of the staff's daily tasks for the period marked done. */
    protected function dailyTaskCompletion(User $staff, int $month, int $year): float
    {
        $base = DailyTask::withoutGlobalScopes()
            ->where('assigned_to', $staff->id)
            ->whereMonth('scheduled_date', $month)
            ->whereYear('scheduled_date', $year);

        $total = (clone $base)->whereIn('status', ['pending', 'in_progress', 'done'])->count();
        if ($total === 0) {
            return 100.0;
        }

        $done = (clone $base)->where('status', 'done')->count();

        return round(($done / $total) * 100, 2);
    }

    /** Team-project contribution: % of the staff's assigned project todos done. */
    protected function teamProjectContribution(User $staff, int $month, int $year): float
    {
        $base = TeamProjectTodo::query()
            ->when(
                \Illuminate\Support\Facades\Schema::hasColumn('team_project_todos', 'assigned_to'),
                fn ($q) => $q->where('assigned_to', $staff->id),
                fn ($q) => $q->whereHas('project.members', fn ($m) => $m->where('user_id', $staff->id))
            );

        $total = (clone $base)->count();
        if ($total === 0) {
            return 100.0;
        }

        $done = (clone $base)->where('status', 'done')->count();

        return round(($done / $total) * 100, 2);
    }

    // ── Finalization ─────────────────────────────────────────────────────────

    /**
     * Compute the weight-normalized overall score and lock the evaluation.
     * Manual lines with no raw_score are treated as 0 (an unscored dimension
     * counts against the staff member — the evaluator must score everything).
     */
    public function finalize(Evaluation $evaluation, ?User $evaluator = null): Evaluation
    {
        $scores = $evaluation->scores()->get();

        $totalWeight = 0.0;
        $weightedSum = 0.0;

        foreach ($scores as $line) {
            $normalized = $line->normalized_score;

            if ($normalized === null) {
                // derive from raw if present, else 0
                $normalized = $line->raw_score !== null
                    ? min(100, ((float) $line->raw_score / max(1, (float) $line->max_score)) * 100)
                    : 0;
            }

            $weight = (float) $line->weight;
            $contribution = $normalized * $weight;

            $line->normalized_score = round((float) $normalized, 2);
            $line->weighted_score   = round($weight > 0 ? $contribution : 0, 2);
            $line->save();

            $totalWeight += $weight;
            $weightedSum += $contribution;
        }

        $overall = $totalWeight > 0 ? round($weightedSum / $totalWeight, 2) : 0;

        // Re-distribute each line's weighted_score as its true contribution to
        // the normalized overall (so the column sums to `overall`).
        if ($totalWeight > 0) {
            foreach ($scores as $line) {
                $share = ((float) $line->weight / $totalWeight) * (float) ($line->normalized_score ?? 0);
                $line->weighted_score = round($share, 2);
                $line->save();
            }
        }

        $evaluation->update([
            'overall_score' => $overall,
            'total_weight'  => round($totalWeight, 2),
            'status'        => 'finalized',
            'finalized_at'  => now(),
            'evaluator_id'  => $evaluator?->id ?? $evaluation->evaluator_id,
        ]);

        return $evaluation->fresh('scores');
    }

    /**
     * Letter grade band for a 0-100 score (used in UI summaries).
     */
    public static function grade(float $score): string
    {
        return match (true) {
            $score >= 90 => 'Outstanding',
            $score >= 80 => 'Exceeds Expectations',
            $score >= 70 => 'Meets Expectations',
            $score >= 60 => 'Needs Improvement',
            default      => 'Unsatisfactory',
        };
    }
}
