<?php

/*
|--------------------------------------------------------------------------
| Lexnora learning configuration
|--------------------------------------------------------------------------
| These are the DEFAULT values. They are copied into the `app_settings`
| table by SettingsSeeder, and the Admin Panel (Phase 7) edits the table.
| Application code must read them through AppSetting::get(), not config().
*/

return [

    'admin' => [
        'email' => env('ADMIN_EMAIL', 'admin@lexnora.test'),
        // No default on purpose: the seeder skips the admin user when empty.
        'password' => env('ADMIN_PASSWORD'),
        'name' => env('ADMIN_NAME', 'Lexnora Admin'),
    ],

    'default_role' => 'learner',

    'defaults' => [
        // --- Dictation scoring ---
        'dictation.correct_threshold' => 100,   // accuracy % needed to count as "correct"
        'dictation.ignore_case' => true,
        'dictation.ignore_punctuation' => true,

        // --- Practice modes (hints allowed per sentence) ---
        'practice.hints.easy' => 3,
        'practice.hints.medium' => 1,
        'practice.hints.hard' => 0,
        'practice.playback_speeds' => [0.5, 0.75, 1, 1.25, 1.5, 2],

        // --- Review schedule (days between reviews, by step) ---
        'review.intervals_days' => [1, 3, 7, 14, 30, 60],

        // --- XP rules (always applied server-side) ---
        'xp.per_correct_sentence' => 10,
        'xp.per_incorrect_attempt' => 2,
        'xp.lesson_completion_bonus' => 25,
        'xp.daily_checkin' => 5,
        'xp.streak_bonus_per_day' => 1,
        'xp.streak_bonus_max' => 10,
        'xp.mode_multiplier.easy' => 1.0,
        'xp.mode_multiplier.medium' => 1.25,
        'xp.mode_multiplier.hard' => 1.5,

        // --- Leaderboard ---
        'leaderboard.metric' => 'xp',           // xp | study_time
        'leaderboard.size' => 50,
    ],
];
