<?php

namespace App\Modules\Review\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Dictation\Exceptions\PracticeStateException;
use App\Modules\Dictation\Models\PracticeSession;
use App\Modules\Dictation\Services\PracticeSessionService;
use App\Modules\Learning\Enums\ReviewStatus;
use App\Modules\Learning\Models\AppSetting;
use App\Modules\Review\Http\Requests\RateVocabularyRequest;
use App\Modules\Review\Models\ReviewHistory;
use App\Modules\Review\Models\ReviewSchedule;
use App\Modules\Review\Services\ReviewScheduleService;
use App\Modules\Vocabulary\Models\SavedVocabulary;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ReviewController extends Controller
{
    public function __construct(
        private readonly ReviewScheduleService $reviews,
        private readonly PracticeSessionService $sessions,
    ) {}

    public function index(Request $request): Response
    {
        $userId = $request->user()->id;
        $history = $request->query('tab') === 'history';

        $due = ReviewSchedule::where('user_id', $userId)->due();
        $dueSentences = (clone $due)->where('reviewable_type', 'sentence')->count();

        $dueVocabulary = (clone $due)->where('reviewable_type', 'saved_vocabulary')
            ->with(['reviewable' => fn (MorphTo $morph) => $morph->morphWith([
                SavedVocabulary::class => ['vocabulary:id,meaning_en,meaning_bn'],
            ])])->orderBy('due_at')->limit(10)->get()
            ->filter(fn (ReviewSchedule $s) => $s->reviewable instanceof SavedVocabulary)
            ->map(fn (ReviewSchedule $s) => [
                'schedule_id' => $s->id,
                'term' => $s->reviewable->term,
                'type' => $s->reviewable->type,
                'context' => $s->reviewable->context_text,
                'meaning' => $s->reviewable->personal_meaning ?? $s->reviewable->vocabulary?->meaning_en,
                'meaning_bn' => $s->reviewable->vocabulary?->meaning_bn,
            ])->values();

        return Inertia::render('review/index', [
            'tab' => $history ? 'history' : 'due',
            'due' => [
                'sentences' => $dueSentences,
                'vocabulary' => (clone $due)->where('reviewable_type', 'saved_vocabulary')->count(),
                'batch' => (int) AppSetting::get('review.batch_size', 10),
            ],
            'vocabulary' => $dueVocabulary,
            'progress' => [
                'active' => ReviewSchedule::where('user_id', $userId)->where('status', ReviewStatus::Active->value)->count(),
                'mastered' => ReviewSchedule::where('user_id', $userId)->where('status', ReviewStatus::Mastered->value)->count(),
                'next_due_at' => ReviewSchedule::where('user_id', $userId)->where('status', ReviewStatus::Active->value)
                    ->where('due_at', '>', now())->min('due_at'),
            ],
            'history' => $history
                ? ReviewHistory::where('user_id', $userId)->with('schedule.reviewable')->latest('reviewed_at')
                    ->paginate(15)->withQueryString()
                    ->through(fn (ReviewHistory $h) => [
                        'id' => $h->id,
                        'label' => $this->labelFor($h->schedule?->reviewable),
                        'kind' => $h->schedule?->reviewable_type,
                        'is_successful' => $h->is_successful,
                        'accuracy' => $h->accuracy !== null ? (float) $h->accuracy : null,
                        'previous_step' => $h->previous_step,
                        'new_step' => $h->new_step,
                        'reviewed_at' => $h->reviewed_at->toIso8601String(),
                    ])
                : null,
        ]);
    }

    public function startSentences(Request $request): RedirectResponse
    {
        $ids = ReviewSchedule::where('user_id', $request->user()->id)->due()
            ->where('reviewable_type', 'sentence')->orderBy('due_at')
            ->limit((int) AppSetting::get('review.batch_size', 10))->pluck('reviewable_id')->all();

        try {
            $session = $this->sessions->startForSentences($request->user(), $ids, PracticeSession::KIND_REVIEW);
        } catch (PracticeStateException) {
            Inertia::flash('toast', ['type' => 'info', 'message' => __('No sentences are due for review.')]);

            return back();
        }

        return to_route('practice.show', $session);
    }

    public function rateVocabulary(RateVocabularyRequest $request, ReviewSchedule $schedule): RedirectResponse
    {
        Gate::authorize('update', $schedule);

        abort_unless($schedule->reviewable_type === 'saved_vocabulary', 404);

        $this->reviews->recordResult($schedule, $request->validated('result') === 'remembered');

        return back();
    }

    private function labelFor(mixed $item): string
    {
        return match (true) {
            $item instanceof SavedVocabulary => $item->term,
            $item !== null && isset($item->english_text) => $item->english_text,
            default => '-',
        };
    }
}
