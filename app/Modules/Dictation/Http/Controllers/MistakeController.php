<?php

namespace App\Modules\Dictation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Dictation\Exceptions\PracticeStateException;
use App\Modules\Dictation\Models\MistakeRecord;
use App\Modules\Dictation\Models\PracticeSession;
use App\Modules\Dictation\Services\PracticeSessionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class MistakeController extends Controller
{
    private const BATCH = 10;

    public function __construct(private readonly PracticeSessionService $sessions) {}

    public function index(Request $request): Response
    {
        $showResolved = $request->query('show') === 'resolved';
        $userId = $request->user()->id;

        $base = MistakeRecord::where('user_id', $userId)->whereHas('sentence');

        $items = (clone $base)
            ->when($showResolved, fn ($q) => $q->whereNotNull('resolved_at'), fn ($q) => $q->unresolved())
            ->with(['sentence:id,english_text,bengali_text', 'latestAttempt'])
            ->latest('last_missed_at')
            ->paginate(10)
            ->withQueryString()
            ->through(fn (MistakeRecord $m) => [
                'id' => $m->id,
                'expected' => $m->sentence->english_text,
                'bengali' => $m->sentence->bengali_text,
                'typed' => $m->latestAttempt?->submitted_text,
                'tokens' => $m->latestAttempt?->error_details['tokens'] ?? [],
                'accuracy' => $m->latestAttempt ? (float) $m->latestAttempt->accuracy : null,
                'times_missed' => $m->times_missed,
                'last_missed_at' => $m->last_missed_at->toIso8601String(),
                'resolved' => $m->resolved_at !== null,
            ]);

        return Inertia::render('mistakes/index', [
            'items' => $items,
            'show' => $showResolved ? 'resolved' : 'open',
            'counts' => [
                'open' => (clone $base)->unresolved()->count(),
                'resolved' => (clone $base)->whereNotNull('resolved_at')->count(),
            ],
        ]);
    }

    public function practiceAgain(Request $request, MistakeRecord $mistake): RedirectResponse
    {
        Gate::authorize('update', $mistake);

        return $this->start($request, [$mistake->sentence_id]);
    }

    public function practiceAll(Request $request): RedirectResponse
    {
        $ids = MistakeRecord::where('user_id', $request->user()->id)->unresolved()->whereHas('sentence')
            ->orderBy('last_missed_at')->limit(self::BATCH)->pluck('sentence_id')->all();

        return $this->start($request, $ids);
    }

    public function dismiss(MistakeRecord $mistake): RedirectResponse
    {
        Gate::authorize('update', $mistake);

        $mistake->update(['resolved_at' => now()]);

        return back();
    }

    /**
     * @param  array<int, string>  $sentenceIds
     */
    private function start(Request $request, array $sentenceIds): RedirectResponse
    {
        try {
            $session = $this->sessions->startForSentences($request->user(), $sentenceIds, PracticeSession::KIND_MISTAKES);
        } catch (PracticeStateException $e) {
            Inertia::flash('toast', ['type' => 'info', 'message' => $e->getMessage()]);

            return back();
        }

        return to_route('practice.show', $session);
    }
}
