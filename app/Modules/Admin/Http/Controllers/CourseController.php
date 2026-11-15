<?php

namespace App\Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Admin\Exceptions\CannotPublish;
use App\Modules\Admin\Http\Requests\CourseRequest;
use App\Modules\Admin\Http\Resources\CategoryResource;
use App\Modules\Admin\Http\Resources\CourseResource;
use App\Modules\Admin\Services\ContentDeletionService;
use App\Modules\Admin\Services\CourseService;
use App\Modules\Admin\Services\PublishingService;
use App\Modules\Courses\Models\Category;
use App\Modules\Courses\Models\Course;
use App\Modules\Learning\Enums\CefrLevel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CourseController extends Controller
{
    public function __construct(
        private readonly CourseService $courses,
        private readonly PublishingService $publishing,
        private readonly ContentDeletionService $deletion,
    ) {}

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Course::class);

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(['published', 'draft'])],
            'cefr_level' => ['nullable', Rule::enum(CefrLevel::class)],
            'category_id' => ['nullable', 'string', 'max:26'],
        ]);

        $courses = Course::query()
            ->with('category:id,name')
            ->withCount(['chapters', 'lessons'])
            ->withSum('lessons', 'estimated_minutes')
            ->when($filters['search'] ?? null, fn ($query, string $search) => $query->where('title', 'like', '%'.addcslashes($search, '%_\\').'%'))
            ->when($filters['status'] ?? null, fn ($query, string $status) => $query->where('is_published', $status === 'published'))
            ->when($filters['cefr_level'] ?? null, fn ($query, string $level) => $query->where('cefr_level', $level))
            ->when($filters['category_id'] ?? null, fn ($query, string $categoryId) => $query->where('category_id', $categoryId))
            ->orderBy('sort_order')
            ->orderBy('title')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Course $course): array => (new CourseResource($course))->resolve());

        return Inertia::render('admin/courses/index', [
            'courses' => $courses,
            'filters' => [
                'search' => $filters['search'] ?? '',
                'status' => $filters['status'] ?? '',
                'cefr_level' => $filters['cefr_level'] ?? '',
                'category_id' => $filters['category_id'] ?? '',
            ],
            'options' => $this->formOptions(),
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', Course::class);

        return Inertia::render('admin/courses/form', [
            'course' => null,
            'options' => $this->formOptions(),
        ]);
    }

    public function store(CourseRequest $request): RedirectResponse
    {
        $course = $this->courses->create(
            $request->validated(),
            $request->file('thumbnail'),
            $request->user(),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Course created. Add chapters and lessons next.')]);

        return to_route('admin.courses.show', $course);
    }

    public function show(Course $course): Response
    {
        Gate::authorize('view', $course);

        $course->load([
            'category:id,name',
            'chapters.lessons' => fn ($lessons) => $lessons->withCount('sentences'),
        ]);

        return Inertia::render('admin/courses/show', [
            'course' => (new CourseResource($course))->resolve(),
        ]);
    }

    public function edit(Course $course): Response
    {
        Gate::authorize('update', $course);

        return Inertia::render('admin/courses/form', [
            'course' => (new CourseResource($course))->resolve(),
            'options' => $this->formOptions(),
        ]);
    }

    public function update(CourseRequest $request, Course $course): RedirectResponse
    {
        $this->courses->update(
            $course,
            $request->validated(),
            $request->file('thumbnail'),
            $request->boolean('remove_thumbnail'),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Course updated.')]);

        return to_route('admin.courses.show', $course);
    }

    public function destroy(Course $course): RedirectResponse
    {
        Gate::authorize('delete', $course);

        $this->deletion->deleteCourse($course);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Course deleted.')]);

        return to_route('admin.courses.index');
    }

    public function togglePublish(Course $course): RedirectResponse
    {
        Gate::authorize('publish', $course);

        try {
            $published = $this->publishing->toggle($course);
        } catch (CannotPublish $e) {
            Inertia::flash('toast', ['type' => 'error', 'message' => $e->getMessage()]);

            return back();
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $published ? __('Course published.') : __('Course unpublished.'),
        ]);

        return back();
    }

    /**
     * @return array{categories: array<int, array<string, mixed>>, cefr_levels: array<int, array{value: string, label: string}>}
     */
    private function formOptions(): array
    {
        return [
            'categories' => CategoryResource::collection(
                Category::query()->active()->orderBy('sort_order')->orderBy('name')->get()
            )->resolve(),
            'cefr_levels' => array_map(
                fn (CefrLevel $level): array => ['value' => $level->value, 'label' => $level->label()],
                CefrLevel::cases()
            ),
        ];
    }
}
