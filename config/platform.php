<?php

return [

    'redis' => (bool) env('REDIS_ENABLED', false),

    'stale_seconds' => (int) env('MATCH_STALE_SECONDS', 30),

    'limits' => [
        'login' => (int) env('LIMIT_LOGIN', 5),
        'register' => (int) env('LIMIT_REGISTER', 5),
        'password_reset' => (int) env('LIMIT_PASSWORD_RESET', 5),
        'matchmaking' => (int) env('LIMIT_MATCHMAKING', 30),
        'next' => (int) env('LIMIT_NEXT', 20),
        'reports' => (int) env('LIMIT_REPORTS', 5),
        'blocks' => (int) env('LIMIT_BLOCKS', 20),
        'signal' => (int) env('LIMIT_SIGNAL', 600),
        'google' => (int) env('LIMIT_GOOGLE', 10),
    ],

    'retention' => [
        'analytics_days' => (int) env('ANALYTICS_RETENTION_DAYS', 180),
        'match_events_days' => (int) env('MATCH_EVENT_RETENTION_DAYS', 90),
    ],

];
