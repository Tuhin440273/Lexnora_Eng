<?php

namespace App\Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Admin\Exceptions\CannotPublish;
use App\Modules\Admin\Http\Requests\ChapterRequest;
use App\Modules\Admin\Http\Requests\MoveRequest;
use App\Modules\Admin\Services\ContentDeletionService;
use App\Modules\Admin\Services\PublishingService;
use App\Modules\Admin\Services\SortOrderService;
use App\Modules\Courses\Models\Chapter;
use App\Modules\Courses\Models\Course;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class ChapterController extends Controller
{
    public function __construct(
        private readonly SortOrderService $order,
        private readonly PublishingService $publishing,
        private readonly ContentDeletionService $deletion,
    ) {}

    public function store(ChapterRequest $request, Course $course): RedirectResponse
    {
        $data = $request->validated();

        Chapter::create([
            'course_id' => $course->id,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'sort_order' => $this->order->next(Chapter::where('course_id', $course->id)),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Chapter added.')]);

        return back();
    }

    public function update(ChapterRequest $request, Chapter $chapter): RedirectResponse
    {
        $data = $request->validated();

        $chapter->update([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Chapter updated.')]);

        return back();
    }

    public function destroy(Chapter $chapter): RedirectResponse
    {
        Gate::authorize('delete', $chapter);

        $this->deletion->deleteChapter($chapter);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Chapter deleted.')]);

        return back();
    }

    public function move(MoveRequest $request, Chapter $chapter): RedirectResponse
    {
        $this->order->move($chapter, Chapter::where('course_id', $chapter->course_id), $request->validated('direction'));

        return back();
    }

    public function togglePublish(Chapter $chapter): RedirectResponse
    {
        Gate::authorize('publish', $chapter);

        try {
            $published = $this->publishing->toggle($chapter);
        } catch (CannotPublish $e) {
            Inertia::flash('toast', ['type' => 'error', 'message' => $e->getMessage()]);

            return back();
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $published ? __('Chapter published.') : __('Chapter unpublished.'),
        ]);

        return back();
    }
}
