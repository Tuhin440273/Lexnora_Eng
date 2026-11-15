<?php

namespace App\Modules\Dictation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Courses\Models\Lesson;
use App\Modules\Courses\Support\LessonAccess;
use App\Modules\Dictation\Exceptions\PracticeStateException;
use App\Modules\Dictation\Http\Requests\AnswerRequest;
use App\Modules\Dictation\Http\Requests\StartPracticeRequest;
use App\Modules\Dictation\Models\PracticeSession;
use App\Modules\Dictation\Services\HintService;
use App\Modules\Dictation\Services\PracticeSessionService;
use App\Modules\Learning\Enums\PracticeMode;
use App\Modules\Learning\Models\AppSetting;
use App\Modules\Learning\Support\TextStats;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class PracticeController extends Controller
{
    public function __construct(
        private readonly PracticeSessionService $sessions,
        private readonly HintService $hints,
    ) {}

    public function start(StartPracticeRequest $request, Lesson $lesson): RedirectResponse
    {
        abort_unless(LessonAccess::canViewLesson($request->user(), $lesson), 404);

        try {
            $session = $this->sessions->startLesson($request->user(), $lesson, PracticeMode::from($request->validated('mode')));
        } catch (PracticeStateException $e) {
            Inertia::flash('toast', ['type' => 'error', 'message' => $e->getMessage()]);

            return back();
        }

        return to_route('practice.show', $session);
    }

    public function show(Request $request, PracticeSession $practiceSession): Response|RedirectResponse
    {
        Gate::authorize('view', $practiceSession);

        $user = $request->user();
        $result = $request->session()->get('practice_result');

        $this->sessions->closeIfExhausted($practiceSession, $user);
        $practiceSession->refresh();

        if ($practiceSession->isFinished() && ! $result) {
            return to_route('practice.summary', $practiceSession);
        }

        $sentence = $practiceSession->isFinished() ? null : $this->sessions->currentSentence($practiceSession);
        $allowedHints = (int) AppSetting::get('practice.hints.'.$practiceSession->mode->value, 0);
        $level = $sentence ? (int) Cache::get($this->sessions->hintKey($practiceSession, $sentence), 0) : 0;
        $audio = $sentence?->primaryAudio;
        $guide = (bool) AppSetting::get('practice.guide_text.'.$practiceSession->mode->value, false);

        return Inertia::render('practice/show', [
            'session' => [
                'id' => $practiceSession->id,
                'mode' => $practiceSession->mode->value,
                'kind' => $practiceSession->kind,
                'position' => min($practiceSession->completed_items + 1, $practiceSession->total_items),
                'total' => $practiceSession->total_items,
                'finished' => $practiceSession->isFinished(),
                'title' => $practiceSession->lesson?->title ?? match ($practiceSession->kind) {
                    PracticeSession::KIND_REVIEW => __('Review'),
                    default => __('Practice again'),
                },
                'back_lesson_id' => $practiceSession->lesson_id,
            ],
            'item' => $sentence ? [
                'id' => $sentence->id,
                'content_type' => $sentence->content_type->value,
                'word_count' => TextStats::wordCount($sentence->english_text),
                'bengali' => $sentence->bengali_text,
                // The faint guide shows the answer, so it is only sent when the admin allows it for this mode.
                'guide_text' => $guide ? $sentence->english_text : null,
                'audio_url' => $audio?->url(),
                // Only without a recording: the browser's own voice reads the text aloud.
                'speak_text' => $audio ? null : $sentence->english_text,
            ] : null,
            'hint' => [
                'allowed' => $allowedHints,
                'used' => min($level, $allowedHints),
                'text' => $sentence && $level > 0 ? $this->hints->hint($sentence->english_text, $level) : null,
            ],
            'result' => $result,
            'speeds' => array_map('floatval', (array) AppSetting::get('practice.playback_speeds', [1])),
            'default_speed' => (float) ($user->profile?->preferences['default_speed'] ?? 1),
            'autoplay' => (bool) ($user->profile?->preferences['autoplay'] ?? true),
            'history' => $practiceSession->attempts()->orderBy('answered_at')->pluck('is_correct'),
        ]);
    }

    public function answer(AnswerRequest $request, PracticeSession $practiceSession): RedirectResponse
    {
        Gate::authorize('update', $practiceSession);

        try {
            $result = $this->sessions->submit($practiceSession, $request->user(), $request->validated());
        } catch (PracticeStateException $e) {
            Inertia::flash('toast', ['type' => 'error', 'message' => $e->getMessage()]);

            return to_route('practice.show', $practiceSession);
        }

        $request->session()->flash('practice_result', $result);

        return to_route('practice.show', $practiceSession);
    }

    public function hint(Request $request, PracticeSession $practiceSession): RedirectResponse
    {
        Gate::authorize('update', $practiceSession);

        $sentence = $practiceSession->isFinished() ? null : $this->sessions->currentSentence($practiceSession);

        if ($sentence === null) {
            return back();
        }

        $allowed = (int) AppSetting::get('practice.hints.'.$practiceSession->mode->value, 0);
        $key = $this->sessions->hintKey($practiceSession, $sentence);
        $level = (int) Cache::get($key, 0);

        if ($level >= $allowed) {
            Inertia::flash('toast', ['type' => 'warning', 'message' => __('No more hints for this one.')]);

            return back();
        }

        Cache::put($key, $level + 1, now()->addDay());

        return back();
    }

    public function summary(PracticeSession $practiceSession): Response|RedirectResponse
    {
        Gate::authorize('view', $practiceSession);

        if (! $practiceSession->isFinished()) {
            return to_route('practice.show', $practiceSession);
        }

        $attempts = $practiceSession->attempts()->with('sentence:id,bengali_text,explanation,content_type')
            ->orderBy('answered_at')->get();

        return Inertia::render('practice/summary', [
            'session' => [
                'id' => $practiceSession->id,
                'mode' => $practiceSession->mode->value,
                'kind' => $practiceSession->kind,
                'status' => $practiceSession->status->value,
                'title' => $practiceSession->lesson?->title ?? match ($practiceSession->kind) {
                    PracticeSession::KIND_REVIEW => __('Review'),
                    default => __('Practice again'),
                },
                'lesson_id' => $practiceSession->lesson_id,
                'total' => $practiceSession->total_items,
                'correct' => $practiceSession->correct_items,
                'average_accuracy' => (float) $practiceSession->average_accuracy,
                'duration_seconds' => $practiceSession->duration_seconds,
                'xp' => $practiceSession->xp_awarded,
            ],
            'attempts' => $attempts->map(fn ($a) => [
                'id' => $a->id,
                'sentence_id' => $a->sentence_id,
                'expected' => $a->expected_text,
                'typed' => $a->submitted_text,
                'accuracy' => (float) $a->accuracy,
                'is_correct' => $a->is_correct,
                'tokens' => $a->error_details['tokens'] ?? [],
                'bengali' => $a->sentence?->bengali_text,
                'hints_used' => $a->hints_used,
            ]),
        ]);
    }
}
