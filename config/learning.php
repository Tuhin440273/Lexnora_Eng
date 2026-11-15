<?php

/*
|--------------------------------------------------------------------------
| Lexnora learning configuration
|--------------------------------------------------------------------------
| The `defaults` below are copied into the `app_settings` table by
| SettingsSeeder, and the Admin Panel edits the table. Application code reads
| them through AppSetting::get(), not config().
*/

return [

    'admin' => [
        'email' => env('ADMIN_EMAIL', 'admin@lexnora.test'),
        // No default on purpose: the seeder skips the admin user when empty.
        'password' => env('ADMIN_PASSWORD'),
        'name' => env('ADMIN_NAME', 'Lexnora Admin'),
    ],

    'default_role' => 'learner',

    /*
    | Uploaded audio. Files are stored on `disk` and streamed through the app
    | (route audio.stream), so no public symlink or APP_URL tweak is needed.
    | Both the file extension and the server-detected MIME type must match.
    */
    'audio' => [
        'disk' => env('AUDIO_DISK', 'local'),
        'max_kb' => (int) env('AUDIO_MAX_KB', 10240),
        'extensions' => ['mp3', 'wav', 'ogg', 'oga', 'm4a', 'aac', 'webm'],
        'mimetypes' => [
            'audio/mpeg', 'audio/mp3', 'audio/x-mpeg',
            'audio/wav', 'audio/x-wav', 'audio/wave', 'audio/vnd.wave',
            'audio/ogg', 'application/ogg',
            'audio/mp4', 'audio/x-m4a', 'audio/m4a', 'video/mp4',
            'audio/aac', 'audio/x-aac',
            'audio/webm', 'video/webm',
        ],
    ],

    'thumbnail' => [
        'disk' => env('THUMBNAIL_DISK', 'local'),
        'max_kb' => 2048,
        'extensions' => ['jpg', 'jpeg', 'png', 'webp'],
    ],

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
