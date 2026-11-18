<?php

use App\Modules\Courses\Http\Controllers\AudioStreamController;
use App\Modules\Courses\Http\Controllers\CourseThumbnailController;
use App\Modules\Learning\Http\Controllers\LanguageController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::post('language', LanguageController::class)->name('language.update');

Route::get('courses/{course}/thumbnail', [CourseThumbnailController::class, 'show'])->name('courses.thumbnail');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('audio/{audio}', [AudioStreamController::class, 'show'])->name('audio.stream');
});

require __DIR__.'/settings.php';
require __DIR__.'/learn.php';
require __DIR__.'/admin.php';
