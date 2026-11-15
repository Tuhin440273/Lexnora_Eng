<?php

use App\Models\User;
use App\Modules\Courses\Models\Sentence;
use App\Modules\Dictation\Models\MistakeRecord;
use App\Modules\Gamification\Models\LearningCheckin;
use App\Modules\Gamification\Models\UserXpTransaction;
use App\Modules\Review\Models\ReviewSchedule;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('rejects a second XP award with the same idempotency key', function () {
    $user = User::factory()->create();
    $award = fn () => UserXpTransaction::create([
        'user_id' => $user->id,
        'amount' => 10,
        'action' => 'dictation_correct',
        'idempotency_key' => 'attempt:abc',
        'awarded_at' => now(),
    ]);

    $award();

    expect($award)->toThrow(QueryException::class);
});

it('keeps one mistake record per user and sentence', function () {
    $user = User::factory()->create();
    $sentence = Sentence::factory()->create();
    $create = fn () => MistakeRecord::create([
        'user_id' => $user->id,
        'sentence_id' => $sentence->id,
        'last_missed_at' => now(),
    ]);

    $create();

    expect($create)->toThrow(QueryException::class);
});

it('allows only one check-in per user per day', function () {
    $user = User::factory()->create();
    $checkin = fn () => LearningCheckin::create(['user_id' => $user->id, 'checkin_date' => today()]);

    $checkin();

    expect($checkin)->toThrow(QueryException::class);
});

it('resolves review items through the morph map', function () {
    $user = User::factory()->create();
    $sentence = Sentence::factory()->create();

    $schedule = new ReviewSchedule(['user_id' => $user->id, 'due_at' => now()->subMinute()]);
    $schedule->reviewable()->associate($sentence);
    $schedule->save();

    expect($schedule->reviewable_type)->toBe('sentence')
        ->and($schedule->fresh()->reviewable->is($sentence))->toBeTrue()
        ->and(ReviewSchedule::due()->count())->toBe(1);
});

it('does not list future reviews as due', function () {
    $user = User::factory()->create();
    $sentence = Sentence::factory()->create();

    $schedule = new ReviewSchedule(['user_id' => $user->id, 'due_at' => now()->addDay()]);
    $schedule->reviewable()->associate($sentence);
    $schedule->save();

    expect(ReviewSchedule::due()->count())->toBe(0);
});
