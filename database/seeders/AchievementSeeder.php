<?php

namespace Database\Seeders;

use App\Models\Achievement;
use Illuminate\Database\Seeder;

class AchievementSeeder extends Seeder
{
    /**
     * Achievement catalog. Keys here MUST match keys in AchievementService::rules()
     * — the service uses the key to look up the row when awarding.
     */
    public function run(): void
    {
        $catalog = [
            // Volume tier
            ['key' => 'first_task_done',        'name' => 'First Win',           'description' => 'Complete your first task.',                              'icon' => '🎯', 'tier' => 'bronze',   'points' => 10],
            ['key' => 'ten_tasks_done',         'name' => 'In the Groove',       'description' => 'Complete 10 tasks.',                                     'icon' => '🚀', 'tier' => 'bronze',   'points' => 25],
            ['key' => 'fifty_tasks_done',       'name' => 'Half Century',        'description' => 'Complete 50 tasks.',                                     'icon' => '⭐', 'tier' => 'silver',   'points' => 75],
            ['key' => 'hundred_tasks_done',     'name' => 'Century Club',        'description' => 'Complete 100 tasks.',                                    'icon' => '💯', 'tier' => 'gold',     'points' => 200],

            // Quality tier
            ['key' => 'on_time_streak_5',       'name' => 'Reliable',            'description' => '5 tasks delivered on time in a row.',                    'icon' => '⏱️', 'tier' => 'bronze',   'points' => 30],
            ['key' => 'on_time_streak_20',      'name' => 'Locked-in',           'description' => '20 tasks delivered on time in a row.',                   'icon' => '🔥', 'tier' => 'gold',     'points' => 150],
            ['key' => 'perfect_month',         'name' => 'Perfect Month',       'description' => 'Score 100% on-time for an entire month (5+ tasks).',     'icon' => '🌟', 'tier' => 'platinum', 'points' => 250],

            // Capacity & collaboration
            ['key' => 'high_complexity_handler','name' => 'Heavy Lifter',        'description' => 'Manage clients summing 20+ complexity points.',          'icon' => '🏋️', 'tier' => 'silver',   'points' => 50],
            ['key' => 'team_player',           'name' => 'Team Player',         'description' => 'Post 25+ comments on tasks.',                            'icon' => '🤝', 'tier' => 'silver',   'points' => 40],
        ];

        foreach ($catalog as $row) {
            Achievement::updateOrCreate(['key' => $row['key']], $row);
        }
    }
}
