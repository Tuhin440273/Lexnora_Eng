<?php

use App\Modules\Analytics\Http\Controllers\AnalyticsController;
use App\Modules\Analytics\Http\Controllers\DashboardController;
use App\Modules\Courses\Http\Controllers\CatalogController;
use App\Modules\Dictation\Http\Controllers\MistakeController;
use App\Modules\Dictation\Http\Controllers\PracticeController;
use App\Modules\Gamification\Http\Controllers\CheckinController;
use App\Modules\Gamification\Http\Controllers\LeaderboardController;
use App\Modules\Learning\Http\Controllers\LearningSettingsController;
use App\Modules\Learning\Http\Controllers\NoteController;
use App\Modules\Review\Http\Controllers\ReviewController;
use App\Modules\Vocabulary\Http\Controllers\SavedVocabularyController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');
    Route::get('analytics', AnalyticsController::class)->name('analytics');
    Route::post('checkin', CheckinController::class)->name('checkin');
    Route::get('leaderboard', [LeaderboardController::class, 'index'])->name('leaderboard');

    Route::get('courses', [CatalogController::class, 'index'])->name('courses.index');
    Route::get('courses/{course}', [CatalogController::class, 'showCourse'])->name('courses.show');
    Route::get('lessons/{lesson}', [CatalogController::class, 'showLesson'])->name('lessons.show');

    Route::post('lessons/{lesson}/practice', [PracticeController::class, 'start'])->name('practice.start');
    Route::get('practice/{practiceSession}', [PracticeController::class, 'show'])->name('practice.show');
    Route::post('practice/{practiceSession}/answer', [PracticeController::class, 'answer'])->middleware('throttle:120,1')->name('practice.answer');
    Route::post('practice/{practiceSession}/hint', [PracticeController::class, 'hint'])->middleware('throttle:60,1')->name('practice.hint');
    Route::get('practice/{practiceSession}/summary', [PracticeController::class, 'summary'])->name('practice.summary');

    Route::get('vocabulary', [SavedVocabularyController::class, 'index'])->name('vocabulary.index');
    Route::post('vocabulary', [SavedVocabularyController::class, 'store'])->name('vocabulary.store');
    Route::patch('vocabulary/{saved}', [SavedVocabularyController::class, 'update'])->name('vocabulary.update');
    Route::patch('vocabulary/{saved}/learned', [SavedVocabularyController::class, 'toggleLearned'])->name('vocabulary.learned');
    Route::delete('vocabulary/{saved}', [SavedVocabularyController::class, 'destroy'])->name('vocabulary.destroy');

    Route::get('mistakes', [MistakeController::class, 'index'])->name('mistakes.index');
    Route::post('mistakes/practice', [MistakeController::class, 'practiceAll'])->name('mistakes.practice-all');
    Route::post('mistakes/{mistake}/practice', [MistakeController::class, 'practiceAgain'])->name('mistakes.practice');
    Route::patch('mistakes/{mistake}/dismiss', [MistakeController::class, 'dismiss'])->name('mistakes.dismiss');

    Route::get('notes', [NoteController::class, 'index'])->name('notes.index');
    Route::post('notes', [NoteController::class, 'store'])->name('notes.store');
    Route::patch('notes/{note}', [NoteController::class, 'update'])->name('notes.update');
    Route::delete('notes/{note}', [NoteController::class, 'destroy'])->name('notes.destroy');

    Route::get('review', [ReviewController::class, 'index'])->name('review.index');
    Route::post('review/start', [ReviewController::class, 'startSentences'])->name('review.start');
    Route::post('review/vocabulary/{schedule}', [ReviewController::class, 'rateVocabulary'])->name('review.rate');

    Route::get('settings/learning', [LearningSettingsController::class, 'edit'])->name('learning-settings.edit');
    Route::patch('settings/learning', [LearningSettingsController::class, 'update'])->name('learning-settings.update');
});
