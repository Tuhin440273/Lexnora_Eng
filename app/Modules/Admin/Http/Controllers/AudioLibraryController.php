<?php

namespace App\Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Admin\Http\Resources\AudioResource;
use App\Modules\Courses\Models\Sentence;
use App\Modules\Courses\Models\SentenceAudio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class AudioLibraryController extends Controller
{
    /**
     * Two views: uploaded files, and sentences that still have no audio.
     */
    public function index(Request $request): Response
    {
        Gate::authorize('manageAudio', Sentence::class);

        $view = $request->query('view') === 'missing' ? 'missing' : 'files';

        $items = $view === 'files'
            ? SentenceAudio::query()
                ->whereHas('sentence')
                ->with('sentence.lesson.chapter.course')
                ->latest('id')
                ->paginate(20)
                ->withQueryString()
                ->through(fn (SentenceAudio $audio): array => [
                    ...(new AudioResource($audio))->resolve(),
                    'english_text' => $audio->sentence->english_text,
                    'lesson' => $this->lessonSummary($audio->sentence),
                ])
            : Sentence::query()
                ->whereDoesntHave('audio')
                ->with('lesson.chapter.course')
                ->orderBy('lesson_id')
                ->orderBy('sort_order')
                ->paginate(20)
                ->withQueryString()
                ->through(fn (Sentence $sentence): array => [
                    'id' => $sentence->id,
                    'english_text' => $sentence->english_text,
                    'content_type' => $sentence->content_type->value,
                    'lesson' => $this->lessonSummary($sentence),
                ]);

        return Inertia::render('admin/audio/index', [
            'view' => $view,
            'items' => $items,
        ]);
    }

    /**
     * @return array{id: string, title: string, course_title: string|null}|null
     */
    private function lessonSummary(Sentence $sentence): ?array
    {
        $lesson = $sentence->lesson;

        return $lesson === null ? null : [
            'id' => $lesson->id,
            'title' => $lesson->title,
            'course_title' => $lesson->chapter?->course?->title,
        ];
    }
}
