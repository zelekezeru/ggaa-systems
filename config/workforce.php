<?php

return [
    'max_capacity' => env('WORKFORCE_MAX_CAPACITY', 30),

    'capacity_thresholds' => [
        'growing'    => 15,
        'balanced'   => 25,
        'overloaded' => 30,
    ],

    'branch_capacity_warning_pct' => env('WORKFORCE_BRANCH_WARN_PCT', 80),

    'achievements_enabled' => env('WORKFORCE_ACHIEVEMENTS_ENABLED', true),
];
