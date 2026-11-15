<?php

namespace App\Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Courses\Models\Chapter;
use App\Modules\Courses\Models\Course;
use App\Modules\Courses\Models\Lesson;
use App\Modules\Courses\Models\Sentence;
use App\Modules\Courses\Models\SentenceAudio;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Content overview for the admin area.
     */
    public function index(): Response
    {
        return Inertia::render('admin/dashboard', [
            'stats' => [
                'courses' => Course::count(),
                'published_courses' => Course::published()->count(),
                'chapters' => Chapter::count(),
                'lessons' => Lesson::count(),
                'sentences' => Sentence::topLevel()->count(),
                'audio_files' => SentenceAudio::whereHas('sentence')->count(),
                'missing_audio' => Sentence::whereDoesntHave('audio')->count(),
                'learners' => User::role('learner')->count(),
            ],
        ]);
    }
}
