<?php

namespace App\Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Admin\Http\Requests\MoveRequest;
use App\Modules\Admin\Http\Requests\SentenceRequest;
use App\Modules\Admin\Services\ContentDeletionService;
use App\Modules\Admin\Services\SortOrderService;
use App\Modules\Courses\Models\Lesson;
use App\Modules\Courses\Models\Sentence;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class SentenceController extends Controller
{
    public function __construct(
        private readonly SortOrderService $order,
        private readonly ContentDeletionService $deletion,
    ) {}

    public function store(SentenceRequest $request, Lesson $lesson): RedirectResponse
    {
        $data = $request->validated();
        $parentId = $data['parent_id'] ?? null;

        Sentence::create([
            'lesson_id' => $lesson->id,
            'parent_id' => $parentId,
            'content_type' => $data['content_type'],
            'english_text' => $data['english_text'],
            'bengali_text' => $data['bengali_text'] ?? null,
            'explanation' => $data['explanation'] ?? null,
            'sort_order' => $this->order->next($this->siblings($lesson->id, $parentId)),
        ]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $parentId ? __('Chunk added.') : __('Sentence added.'),
        ]);

        return back();
    }

    public function update(SentenceRequest $request, Sentence $sentence): RedirectResponse
    {
        $data = $request->validated();

        $sentence->update([
            'content_type' => $data['content_type'],
            'english_text' => $data['english_text'],
            'bengali_text' => $data['bengali_text'] ?? null,
            'explanation' => $data['explanation'] ?? null,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Saved.')]);

        return back();
    }

    public function destroy(Sentence $sentence): RedirectResponse
    {
        Gate::authorize('delete', $sentence);

        $this->deletion->deleteSentence($sentence);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Deleted.')]);

        return back();
    }

    public function move(MoveRequest $request, Sentence $sentence): RedirectResponse
    {
        $this->order->move(
            $sentence,
            $this->siblings($sentence->lesson_id, $sentence->parent_id),
            $request->validated('direction'),
        );

        return back();
    }

    /**
     * Rows that share the same lesson and the same parent (or all top-level rows).
     *
     * @return Builder<Sentence>
     */
    private function siblings(string $lessonId, ?string $parentId)
    {
        return Sentence::query()
            ->where('lesson_id', $lessonId)
            ->when(
                $parentId,
                fn ($query) => $query->where('parent_id', $parentId),
                fn ($query) => $query->whereNull('parent_id'),
            );
    }
}
