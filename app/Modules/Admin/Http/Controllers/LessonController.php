<?php

namespace App\Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Admin\Exceptions\CannotPublish;
use App\Modules\Admin\Http\Requests\LessonRequest;
use App\Modules\Admin\Http\Requests\MoveRequest;
use App\Modules\Admin\Http\Resources\LessonResource;
use App\Modules\Admin\Http\Resources\SentenceResource;
use App\Modules\Admin\Services\ContentDeletionService;
use App\Modules\Admin\Services\PublishingService;
use App\Modules\Admin\Services\SortOrderService;
use App\Modules\Courses\Models\Chapter;
use App\Modules\Courses\Models\Lesson;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class LessonController extends Controller
{
    public function __construct(
        private readonly SortOrderService $order,
        private readonly PublishingService $publishing,
        private readonly ContentDeletionService $deletion,
    ) {}

    public function store(LessonRequest $request, Chapter $chapter): RedirectResponse
    {
        $data = $request->validated();

        Lesson::create([
            'chapter_id' => $chapter->id,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'difficulty' => $data['difficulty'],
            'estimated_minutes' => $data['estimated_minutes'],
            'sort_order' => $this->order->next(Lesson::where('chapter_id', $chapter->id)),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Lesson added.')]);

        return back();
    }

    public function show(Lesson $lesson): Response
    {
        Gate::authorize('view', $lesson);

        $lesson->load('chapter.course:id,title');

        $sentences = $lesson->sentences()
            ->with(['audio', 'children.audio'])
            ->get();

        return Inertia::render('admin/lessons/show', [
            'lesson' => (new LessonResource($lesson))->resolve(),
            'chapter' => ['id' => $lesson->chapter->id, 'title' => $lesson->chapter->title],
            'course' => ['id' => $lesson->chapter->course->id, 'title' => $lesson->chapter->course->title],
            'sentences' => SentenceResource::collection($sentences)->resolve(),
        ]);
    }

    public function update(LessonRequest $request, Lesson $lesson): RedirectResponse
    {
        $data = $request->validated();

        $lesson->update([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'difficulty' => $data['difficulty'],
            'estimated_minutes' => $data['estimated_minutes'],
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Lesson updated.')]);

        return back();
    }

    public function destroy(Lesson $lesson): RedirectResponse
    {
        Gate::authorize('delete', $lesson);

        $courseId = $lesson->chapter->course_id;

        $this->deletion->deleteLesson($lesson);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Lesson deleted.')]);

        return to_route('admin.courses.show', $courseId);
    }

    public function move(MoveRequest $request, Lesson $lesson): RedirectResponse
    {
        $this->order->move($lesson, Lesson::where('chapter_id', $lesson->chapter_id), $request->validated('direction'));

        return back();
    }

    public function togglePublish(Lesson $lesson): RedirectResponse
    {
        Gate::authorize('publish', $lesson);

        try {
            $published = $this->publishing->toggle($lesson);
        } catch (CannotPublish $e) {
            Inertia::flash('toast', ['type' => 'error', 'message' => $e->getMessage()]);

            return back();
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $published ? __('Lesson published.') : __('Lesson unpublished.'),
        ]);

        return back();
    }
}
