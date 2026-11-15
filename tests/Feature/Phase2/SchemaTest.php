<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

it('creates every table of the architecture', function (string $table) {
    expect(Schema::hasTable($table))->toBeTrue("Missing table: {$table}");
})->with([
    'user_profiles', 'categories', 'courses', 'chapters', 'lessons', 'sentences',
    'sentence_audio', 'vocabulary', 'sentence_vocabulary', 'practice_sessions',
    'practice_attempts', 'user_course_progress', 'user_lesson_progress',
    'saved_vocabulary', 'mistake_records', 'review_schedules', 'review_history',
    'user_notes', 'learning_checkins', 'user_xp_transactions', 'badges',
    'user_badges', 'daily_learning_statistics', 'app_settings',
    // created by spatie/laravel-permission
    'roles', 'permissions', 'model_has_roles', 'model_has_permissions', 'role_has_permissions',
]);

it('has the columns the hierarchy needs', function () {
    expect(Schema::hasColumns('sentences', [
        'lesson_id', 'parent_id', 'content_type', 'english_text',
        'bengali_text', 'explanation', 'sort_order', 'deleted_at',
    ]))->toBeTrue();

    expect(Schema::hasColumns('courses', [
        'category_id', 'title', 'slug', 'description', 'thumbnail_path',
        'cefr_level', 'difficulty', 'is_published', 'deleted_at',
    ]))->toBeTrue();
});

it('stores audio as a file path and never as binary data', function () {
    expect(Schema::hasColumns('sentence_audio', ['disk', 'path', 'duration_ms']))->toBeTrue();

    foreach (Schema::getColumns('sentence_audio') as $column) {
        expect(strtolower($column['type_name']))->not->toContain('blob');
    }
});

it('has soft deletes on user-facing content tables', function (string $table) {
    expect(Schema::hasColumn($table, 'deleted_at'))->toBeTrue();
})->with(['categories', 'courses', 'chapters', 'lessons', 'sentences', 'sentence_audio', 'saved_vocabulary', 'user_notes']);
