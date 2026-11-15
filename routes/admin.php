<?php

use App\Modules\Admin\Http\Controllers\AudioLibraryController;
use App\Modules\Admin\Http\Controllers\CategoryController;
use App\Modules\Admin\Http\Controllers\ChapterController;
use App\Modules\Admin\Http\Controllers\CourseController;
use App\Modules\Admin\Http\Controllers\DashboardController;
use App\Modules\Admin\Http\Controllers\LessonController;
use App\Modules\Admin\Http\Controllers\SentenceAudioController;
use App\Modules\Admin\Http\Controllers\SentenceController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'can:access-admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

        Route::resource('categories', CategoryController::class)->only(['index', 'store', 'update', 'destroy']);

        Route::resource('courses', CourseController::class);
        Route::patch('courses/{course}/publish', [CourseController::class, 'togglePublish'])->name('courses.publish');

        Route::post('courses/{course}/chapters', [ChapterController::class, 'store'])->name('chapters.store');
        Route::patch('chapters/{chapter}', [ChapterController::class, 'update'])->name('chapters.update');
        Route::delete('chapters/{chapter}', [ChapterController::class, 'destroy'])->name('chapters.destroy');
        Route::patch('chapters/{chapter}/move', [ChapterController::class, 'move'])->name('chapters.move');
        Route::patch('chapters/{chapter}/publish', [ChapterController::class, 'togglePublish'])->name('chapters.publish');

        Route::post('chapters/{chapter}/lessons', [LessonController::class, 'store'])->name('lessons.store');
        Route::get('lessons/{lesson}', [LessonController::class, 'show'])->name('lessons.show');
        Route::patch('lessons/{lesson}', [LessonController::class, 'update'])->name('lessons.update');
        Route::delete('lessons/{lesson}', [LessonController::class, 'destroy'])->name('lessons.destroy');
        Route::patch('lessons/{lesson}/move', [LessonController::class, 'move'])->name('lessons.move');
        Route::patch('lessons/{lesson}/publish', [LessonController::class, 'togglePublish'])->name('lessons.publish');

        Route::post('lessons/{lesson}/sentences', [SentenceController::class, 'store'])->name('sentences.store');
        Route::patch('sentences/{sentence}', [SentenceController::class, 'update'])->name('sentences.update');
        Route::delete('sentences/{sentence}', [SentenceController::class, 'destroy'])->name('sentences.destroy');
        Route::patch('sentences/{sentence}/move', [SentenceController::class, 'move'])->name('sentences.move');

        Route::get('audio', [AudioLibraryController::class, 'index'])->name('audio.index');
        Route::post('sentences/{sentence}/audio', [SentenceAudioController::class, 'store'])->name('audio.store');
        Route::patch('audio/{audio}/primary', [SentenceAudioController::class, 'makePrimary'])->name('audio.primary');
        Route::delete('audio/{audio}', [SentenceAudioController::class, 'destroy'])->name('audio.destroy');
    });
