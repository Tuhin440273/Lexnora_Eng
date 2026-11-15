<?php

namespace App\Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Admin\Http\Requests\VocabularyRequest;
use App\Modules\Admin\Services\VocabularyLinker;
use App\Modules\Learning\Enums\CefrLevel;
use App\Modules\Vocabulary\Models\Vocabulary;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class VocabularyController extends Controller
{
    public function __construct(private readonly VocabularyLinker $linker) {}

    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'type' => ['nullable', Rule::in(['word', 'phrase'])],
            'cefr_level' => ['nullable', Rule::enum(CefrLevel::class)],
            'status' => ['nullable', Rule::in(['active', 'hidden'])],
        ]);

        $entries = Vocabulary::query()
            ->withCount('sentences')
            ->when($filters['search'] ?? null, function ($q, string $search) {
                $like = '%'.addcslashes($search, '%_\\').'%';
                $q->where(fn ($q) => $q->where('term', 'like', $like)
                    ->orWhere('meaning_en', 'like', $like)->orWhere('meaning_bn', 'like', $like));
            })
            ->when($filters['type'] ?? null, fn ($q, string $type) => $q->where('type', $type))
            ->when($filters['cefr_level'] ?? null, fn ($q, string $level) => $q->where('cefr_level', $level))
            ->when($filters['status'] ?? null, fn ($q, string $status) => $q->where('is_active', $status === 'active'))
            ->orderBy('normalized_term')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Vocabulary $v) => [
                'id' => $v->id,
                'term' => $v->term,
                'type' => $v->type,
                'part_of_speech' => $v->part_of_speech,
                'meaning_en' => $v->meaning_en,
                'meaning_bn' => $v->meaning_bn,
                'pronunciation' => $v->pronunciation,
                'example' => $v->example,
                'cefr_level' => $v->cefr_level?->value,
                'is_active' => $v->is_active,
                'sentences_count' => $v->sentences_count,
            ]);

        return Inertia::render('admin/vocabulary/index', [
            'entries' => $entries,
            'filters' => [
                'search' => $filters['search'] ?? '',
                'type' => $filters['type'] ?? '',
                'cefr_level' => $filters['cefr_level'] ?? '',
                'status' => $filters['status'] ?? '',
            ],
            'cefr_levels' => array_map(fn (CefrLevel $l) => ['value' => $l->value, 'label' => $l->label()], CefrLevel::cases()),
        ]);
    }

    public function store(VocabularyRequest $request): RedirectResponse
    {
        $entry = Vocabulary::create($this->fields($request) + ['created_by' => $request->user()->id]);

        $linked = $this->linker->linkVocabulary($entry);

        $this->toast(__('Added. Linked to :count sentence(s).', ['count' => $linked]));

        return back();
    }

    public function update(VocabularyRequest $request, Vocabulary $vocabulary): RedirectResponse
    {
        $vocabulary->update($this->fields($request));

        $linked = $this->linker->linkVocabulary($vocabulary);

        $this->toast($linked > 0 ? __('Saved. Linked to :count more sentence(s).', ['count' => $linked]) : __('Saved.'));

        return back();
    }

    public function destroy(Request $request, Vocabulary $vocabulary): RedirectResponse
    {
        abort_unless($request->user()->can('vocabulary.manage'), 403);

        $vocabulary->delete();

        $this->toast(__('Removed. Saved words in learners\' notebooks are kept.'));

        return back();
    }

    public function relink(Request $request, Vocabulary $vocabulary): RedirectResponse
    {
        abort_unless($request->user()->can('vocabulary.manage'), 403);

        $linked = $this->linker->linkVocabulary($vocabulary);

        $this->toast($linked > 0 ? __('Linked to :count more sentence(s).', ['count' => $linked]) : __('No new matching sentences were found.'));

        return back();
    }

    /**
     * @return array<string, mixed>
     */
    private function fields(VocabularyRequest $request): array
    {
        $data = $request->safe()->except(['normalized_term', 'is_active']);

        return $data + ['is_active' => $request->boolean('is_active')];
    }

    private function toast(string $message): void
    {
        Inertia::flash('toast', ['type' => 'success', 'message' => $message]);
    }
}
