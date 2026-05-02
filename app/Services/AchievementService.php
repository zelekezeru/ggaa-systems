<?php

namespace App\Services;

use App\Models\Achievement;
use App\Models\Task;
use App\Models\User;
use App\Notifications\AchievementUnlockedNotification;

class AchievementService
{
    /**
     * Evaluate all rules for a user and award any newly-earned achievements.
     * Returns the list of achievements freshly unlocked in this run.
     */
    public function evaluate(User $user): array
    {
        if (! config('workforce.achievements_enabled', true)) {
            return [];
        }

        $alreadyEarned = $user->achievements()->pluck('achievements.key')->all();
        $unlocked = [];

        foreach ($this->rules() as $key => $rule) {
            if (in_array($key, $alreadyEarned, true)) {
                continue;
            }

            if (! ($rule['check'])($user)) {
                continue;
            }

            $achievement = Achievement::where('key', $key)->where('is_active', true)->first();
            if (! $achievement) {
                continue;
            }

            $user->achievements()->attach($achievement->id, ['earned_at' => now()]);
            $user->notify(new AchievementUnlockedNotification($achievement));
            $unlocked[] = $achievement;
        }

        return $unlocked;
    }

    /**
     * Catalogue of achievement rules. Each rule is keyed by Achievement.key
     * and exposes a `check` closure that takes a User and returns bool.
     *
     * Add new rules here — no other code change needed.
     */
    private function rules(): array
    {
        return [
            'first_task_done' => [
                'check' => fn (User $u) => Task::where('assigned_user_id', $u->id)
                    ->where('status', 'Done')
                    ->withoutGlobalScopes()
                    ->exists(),
            ],

            'ten_tasks_done' => [
                'check' => fn (User $u) => Task::where('assigned_user_id', $u->id)
                    ->where('status', 'Done')
                    ->withoutGlobalScopes()
                    ->count() >= 10,
            ],

            'fifty_tasks_done' => [
                'check' => fn (User $u) => Task::where('assigned_user_id', $u->id)
                    ->where('status', 'Done')
                    ->withoutGlobalScopes()
                    ->count() >= 50,
            ],

            'hundred_tasks_done' => [
                'check' => fn (User $u) => Task::where('assigned_user_id', $u->id)
                    ->where('status', 'Done')
                    ->withoutGlobalScopes()
                    ->count() >= 100,
            ],

            'on_time_streak_5' => [
                'check' => fn (User $u) => $this->onTimeStreak($u) >= 5,
            ],

            'on_time_streak_20' => [
                'check' => fn (User $u) => $this->onTimeStreak($u) >= 20,
            ],

            'perfect_month' => [
                // Last completed calendar month: 100% on-time score, at least 5 tasks
                'check' => function (User $u) {
                    $month = now()->subMonthNoOverflow();
                    $taskCount = Task::where('assigned_user_id', $u->id)
                        ->whereMonth('due_date', $month->month)
                        ->whereYear('due_date', $month->year)
                        ->withoutGlobalScopes()
                        ->count();

                    if ($taskCount < 5) return false;

                    return $u->getWeightedPerformanceScore($month->month, $month->year) >= 100;
                },
            ],

            'high_complexity_handler' => [
                // Carrying clients summing to >= 20 complexity points
                'check' => fn (User $u) => $u->getCurrentCapacityLoad() >= 20,
            ],

            'team_player' => [
                // Posted 25+ comments on tasks
                'check' => fn (User $u) => \App\Models\TaskComment::where('user_id', $u->id)->count() >= 25,
            ],
        ];
    }

    /**
     * Counts how many tasks were completed in a row on time
     * (most-recent first) before encountering a late one.
     */
    private function onTimeStreak(User $u): int
    {
        $tasks = Task::where('assigned_user_id', $u->id)
            ->where('status', 'Done')
            ->whereNotNull('completed_at')
            ->whereNotNull('due_date')
            ->withoutGlobalScopes()
            ->orderByDesc('completed_at')
            ->get(['completed_at', 'due_date']);

        $streak = 0;
        foreach ($tasks as $t) {
            if ($t->completed_at <= $t->due_date) {
                $streak++;
            } else {
                break;
            }
        }
        return $streak;
    }
}
