<?php

namespace App\Modules\Courses\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Courses\Models\Category;
use App\Modules\Courses\Models\Course;
use App\Modules\Courses\Models\Lesson;
use App\Modules\Courses\Models\Sentence;
use App\Modules\Courses\Support\LessonAccess;
use App\Modules\Dictation\Models\PracticeSession;
use App\Modules\Learning\Enums\CefrLevel;
use App\Modules\Learning\Enums\ContentType;
use App\Modules\Learning\Enums\PracticeMode;
use App\Modules\Learning\Enums\ProgressStatus;
use App\Modules\Learning\Enums\SessionStatus;
use App\Modules\Learning\Models\AppSetting;
use App\Modules\Learning\Models\UserLessonProgress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CatalogController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'cefr_level' => ['nullable', Rule::enum(CefrLevel::class)],
            'category_id' => ['nullable', 'string', 'max:26'],
        ]);

        $user = $request->user();

        $courses = Course::published()
            ->with('category:id,name')
            ->when($filters['search'] ?? null, fn ($q, string $s) => $q->where('title', 'like', '%'.addcslashes($s, '%_\\').'%'))
            ->when($filters['cefr_level'] ?? null, fn ($q, string $level) => $q->where('cefr_level', $level))
            ->when($filters['category_id'] ?? null, fn ($q, string $id) => $q->where('category_id', $id))
            ->orderBy('sort_order')->orderBy('title')
            ->paginate(12)
            ->withQueryString();

        $ids = $courses->getCollection()->pluck('id');

        $totals = Lesson::learnerVisible()
            ->join('chapters', 'lessons.chapter_id', '=', 'chapters.id')
            ->whereIn('chapters.course_id', $ids)
            ->groupBy('chapters.course_id')
            ->selectRaw('chapters.course_id as course_id, count(*) as lessons, sum(lessons.estimated_minutes) as minutes')
            ->get()->keyBy('course_id');

        $completed = DB::table('user_lesson_progress')
            ->join('lessons', 'lessons.id', '=', 'user_lesson_progress.lesson_id')
            ->join('chapters', 'chapters.id', '=', 'lessons.chapter_id')
            ->where('user_lesson_progress.user_id', $user->id)
            ->where('user_lesson_progress.status', ProgressStatus::Completed->value)
            ->whereIn('chapters.course_id', $ids)
            ->where('lessons.is_published', true)->whereNull('lessons.deleted_at')
            ->where('chapters.is_published', true)->whereNull('chapters.deleted_at')
            ->groupBy('chapters.course_id')
            ->selectRaw('chapters.course_id as course_id, count(*) as total')
            ->pluck('total', 'course_id');

        return Inertia::render('courses/index', [
            'courses' => $courses->through(function (Course $course) use ($totals, $completed) {
                $total = (int) ($totals[$course->id]->lessons ?? 0);
                $done = (int) ($completed[$course->id] ?? 0);

                return [
                    'id' => $course->id,
                    'title' => $course->title,
                    'description' => $course->description,
                    'cefr_level' => $course->cefr_level->value,
                    'category' => $course->category?->name,
                    'thumbnail_url' => $course->thumbnail_path ? route('courses.thumbnail', ['course' => $course->id]) : null,
                    'lessons_count' => $total,
                    'minutes' => (int) ($totals[$course->id]->minutes ?? 0),
                    'completed_lessons' => $done,
                    'progress' => $total > 0 ? (int) round($done / $total * 100) : 0,
                ];
            }),
            'filters' => [
                'search' => $filters['search'] ?? '',
                'cefr_level' => $filters['cefr_level'] ?? '',
                'category_id' => $filters['category_id'] ?? '',
            ],
            'options' => [
                'categories' => Category::active()->orderBy('sort_order')->orderBy('name')->get(['id', 'name']),
                'cefr_levels' => array_map(fn (CefrLevel $l) => ['value' => $l->value, 'label' => $l->label()], CefrLevel::cases()),
            ],
        ]);
    }

    public function showCourse(Request $request, Course $course): Response
    {
        $user = $request->user();
        abort_unless(LessonAccess::canViewCourse($user, $course), 404);

        $preview = $user->canAccessAdmin();

        $course->load([
            'category:id,name',
            'chapters' => fn ($q) => $q->when(! $preview, fn ($q) => $q->where('is_published', true))
                ->with(['lessons' => fn ($l) => $l->when(! $preview, fn ($l) => $l->where('is_published', true))->withCount('sentences')]),
        ]);

        $lessonIds = $course->chapters->flatMap->lessons->pluck('id');
        $progress = UserLessonProgress::where('user_id', $user->id)->whereIn('lesson_id', $lessonIds)->get()->keyBy('lesson_id');

        return Inertia::render('courses/show', [
            'course' => [
                'id' => $course->id,
                'title' => $course->title,
                'description' => $course->description,
                'cefr_label' => $course->cefr_level->label(),
                'category' => $course->category?->name,
                'is_published' => $course->is_published,
                'thumbnail_url' => $course->thumbnail_path ? route('courses.thumbnail', ['course' => $course->id]) : null,
                'chapters' => $course->chapters->map(fn ($chapter) => [
                    'id' => $chapter->id,
                    'title' => $chapter->title,
                    'description' => $chapter->description,
                    'lessons' => $chapter->lessons->map(fn (Lesson $lesson) => [
                        'id' => $lesson->id,
                        'title' => $lesson->title,
                        'description' => $lesson->description,
                        'difficulty' => $lesson->difficulty,
                        'estimated_minutes' => $lesson->estimated_minutes,
                        'sentences_count' => $lesson->sentences_count,
                        'status' => ($progress[$lesson->id]->status ?? ProgressStatus::NotStarted)->value,
                        'best_accuracy' => isset($progress[$lesson->id]) ? (float) $progress[$lesson->id]->best_accuracy : null,
                    ])->values(),
                ])->values(),
            ],
        ]);
    }

    public function showLesson(Request $request, Lesson $lesson): Response
    {
        $user = $request->user();
        abort_unless(LessonAccess::canViewLesson($user, $lesson), 404);

        $lesson->load('chapter.course');

        $topLevel = Sentence::where('lesson_id', $lesson->id)->whereNull('parent_id')->count();
        $chunks = Sentence::where('lesson_id', $lesson->id)->whereNotNull('parent_id')
            ->select('content_type', DB::raw('count(*) as total'))->groupBy('content_type')->pluck('total', 'content_type');

        $unfinished = PracticeSession::where('user_id', $user->id)->where('lesson_id', $lesson->id)
            ->where('kind', PracticeSession::KIND_LESSON)->where('status', SessionStatus::InProgress->value)->get()
            ->keyBy(fn ($s) => $s->mode->value);

        $progress = UserLessonProgress::where('user_id', $user->id)->where('lesson_id', $lesson->id)->first();

        $modes = collect(PracticeMode::cases())->map(fn (PracticeMode $mode) => [
            'value' => $mode->value,
            'label' => $mode->label(),
            'hints' => (int) AppSetting::get('practice.hints.'.$mode->value, 0),
            'guide' => (bool) AppSetting::get('practice.guide_text.'.$mode->value, false),
            'items' => match ($mode) {
                PracticeMode::Hard => $topLevel,
                PracticeMode::Medium => $topLevel + (int) ($chunks[ContentType::Phrase->value] ?? 0),
                PracticeMode::Easy => $topLevel + (int) ($chunks[ContentType::Phrase->value] ?? 0) + (int) ($chunks[ContentType::Word->value] ?? 0),
            },
            'resume' => isset($unfinished[$mode->value])
                ? ['done' => $unfinished[$mode->value]->completed_items, 'total' => $unfinished[$mode->value]->total_items]
                : null,
        ])->values();

        return Inertia::render('lessons/show', [
            'lesson' => [
                'id' => $lesson->id,
                'title' => $lesson->title,
                'description' => $lesson->description,
                'difficulty' => $lesson->difficulty,
                'estimated_minutes' => $lesson->estimated_minutes,
                'sentences_count' => $topLevel,
                'is_published' => $lesson->is_published,
            ],
            'chapter' => ['id' => $lesson->chapter->id, 'title' => $lesson->chapter->title],
            'course' => ['id' => $lesson->chapter->course->id, 'title' => $lesson->chapter->course->title],
            'modes' => $modes,
            'progress' => $progress ? [
                'status' => $progress->status->value,
                'best_accuracy' => $progress->best_accuracy !== null ? (float) $progress->best_accuracy : null,
                'sessions_count' => $progress->sessions_count,
            ] : null,
            'recent' => PracticeSession::where('user_id', $user->id)->where('lesson_id', $lesson->id)
                ->where('status', SessionStatus::Completed->value)->latest('completed_at')->limit(5)->get()
                ->map(fn ($s) => [
                    'id' => $s->id, 'mode' => $s->mode->value, 'accuracy' => (float) $s->average_accuracy,
                    'completed_at' => $s->completed_at?->toIso8601String(),
                ]),
        ]);
    }
}
