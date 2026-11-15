<?php

namespace App\Modules\Vocabulary\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Courses\Models\Sentence;
use App\Modules\Courses\Support\LessonAccess;
use App\Modules\Review\Models\ReviewSchedule;
use App\Modules\Review\Services\ReviewScheduleService;
use App\Modules\Vocabulary\Http\Requests\SavedVocabularyRequest;
use App\Modules\Vocabulary\Models\SavedVocabulary;
use App\Modules\Vocabulary\Models\Vocabulary;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class SavedVocabularyController extends Controller
{
    public function __construct(private readonly ReviewScheduleService $reviews) {}

    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(['learned', 'learning'])],
        ]);

        $items = SavedVocabulary::where('user_id', $request->user()->id)
            ->with('vocabulary:id,meaning_en,meaning_bn,pronunciation')
            ->when($filters['search'] ?? null, fn ($q, string $s) => $q->search($s))
            ->when($filters['status'] ?? null, fn ($q, string $status) => $q->where('is_learned', $status === 'learned'))
            ->latest('id')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (SavedVocabulary $v) => [
                'id' => $v->id,
                'term' => $v->term,
                'type' => $v->type,
                'context_text' => $v->context_text,
                'personal_meaning' => $v->personal_meaning,
                'notes' => $v->notes,
                'is_learned' => $v->is_learned,
                'meaning_en' => $v->vocabulary?->meaning_en,
                'meaning_bn' => $v->vocabulary?->meaning_bn,
                'pronunciation' => $v->vocabulary?->pronunciation,
            ]);

        return Inertia::render('vocabulary/index', [
            'items' => $items,
            'filters' => ['search' => $filters['search'] ?? '', 'status' => $filters['status'] ?? ''],
            'totals' => [
                'all' => SavedVocabulary::where('user_id', $request->user()->id)->count(),
                'learned' => SavedVocabulary::where('user_id', $request->user()->id)->learned()->count(),
            ],
        ]);
    }

    public function store(SavedVocabularyRequest $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validated();
        $type = $data['type'] ?? (str_contains(trim($data['term']), ' ') ? 'phrase' : 'word');
        $normalized = SavedVocabulary::normalizeTerm($data['term']);

        $existing = SavedVocabulary::where('user_id', $user->id)->where('normalized_term', $normalized)->first();

        if ($existing) {
            $existing->fill(array_filter([
                'personal_meaning' => $data['personal_meaning'] ?? null,
                'notes' => $data['notes'] ?? null,
            ], fn ($value) => $value !== null))->save();

            Inertia::flash('toast', ['type' => 'info', 'message' => __('This is already in your vocabulary notebook.')]);

            return back();
        }

        // The sentence is kept as context only when the learner may see it.
        $sentence = ! empty($data['context_sentence_id'])
            ? Sentence::with('lesson')->find($data['context_sentence_id'])
            : null;
        $sentence = $sentence && $sentence->lesson && LessonAccess::canViewLesson($user, $sentence->lesson) ? $sentence : null;

        $saved = SavedVocabulary::create([
            'user_id' => $user->id,
            'vocabulary_id' => Vocabulary::where('normalized_term', $normalized)->where('is_active', true)->value('id'),
            'context_sentence_id' => $sentence?->id,
            'term' => trim($data['term']),
            'type' => $type,
            'context_text' => $sentence?->english_text,
            'personal_meaning' => $data['personal_meaning'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);

        $this->reviews->ensureScheduled($user, $saved);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Saved to your vocabulary notebook.')]);

        return back();
    }

    public function update(SavedVocabularyRequest $request, SavedVocabulary $saved): RedirectResponse
    {
        Gate::authorize('update', $saved);

        $saved->update([
            'personal_meaning' => $request->validated('personal_meaning'),
            'notes' => $request->validated('notes'),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Saved.')]);

        return back();
    }

    public function toggleLearned(SavedVocabulary $saved): RedirectResponse
    {
        Gate::authorize('update', $saved);

        $learned = ! $saved->is_learned;
        $saved->update(['is_learned' => $learned, 'learned_at' => $learned ? now() : null]);

        return back();
    }

    public function destroy(SavedVocabulary $saved): RedirectResponse
    {
        Gate::authorize('delete', $saved);

        ReviewSchedule::where('user_id', $saved->user_id)
            ->where('reviewable_type', $saved->getMorphClass())
            ->where('reviewable_id', $saved->id)
            ->delete();

        $saved->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Removed from your notebook.')]);

        return back();
    }
}
