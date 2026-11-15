<?php

use App\Models\User;
use App\Modules\Courses\Models\Category;
use App\Modules\Courses\Models\Chapter;
use App\Modules\Courses\Models\Course;
use App\Modules\Courses\Models\Lesson;
use App\Modules\Courses\Models\Sentence;
use App\Modules\Gamification\Models\UserXpTransaction;
use App\Modules\Learning\Models\UserNote;
use App\Modules\Review\Services\ReviewScheduleService;
use App\Modules\Vocabulary\Models\SavedVocabulary;
use App\Modules\Vocabulary\Models\Vocabulary;
use Illuminate\Support\Facades\DB;

require_once __DIR__.'/../Phase4/Helpers.php';

/*
| A page must run the same number of queries for 2 rows as for 10 rows.
| If it grows with the data, there is a hidden query per row (an "N+1").
| Lazy loading is also forbidden in tests, so a missed eager load fails loudly.
*/

beforeEach(function () {
    seedRoles();
    $this->withoutVite();
});

function queryCount(callable $request): int
{
    DB::flushQueryLog();
    DB::enableQueryLog();
    $request();
    $count = count(DB::getQueryLog());
    DB::disableQueryLog();

    return $count;
}

function assertScales(string $url, callable $addData, ?User $as = null, ?string $role = null): void
{
    $as ??= $role ? userWithRole($role) : learner();

    $addData($as, 2);
    test()->actingAs($as)->get($url)->assertOk();   // warm-up: permission cache, settings cache

    $small = queryCount(fn () => test()->actingAs($as)->get($url)->assertOk());

    $addData($as, 8);
    $large = queryCount(fn () => test()->actingAs($as)->get($url)->assertOk());

    expect($large)->toBeLessThanOrEqual($small, "{$url}: {$small} queries for 2 rows, {$large} for 10");
}

it('lists the course catalog in a fixed number of queries', function () {
    assertScales(route('courses.index'), function ($user, int $count) {
        foreach (range(1, $count) as $ignored) {
            $lesson = publishedLesson(1);
            $lesson->chapter->course->update(['category_id' => Category::factory()->create()->id]);
        }
    });
});

it('shows a course page in a fixed number of queries', function () {
    $course = Course::factory()->published()->create();
    assertScales(route('courses.show', $course), function ($user, int $count) use ($course) {
        $chapter = Chapter::factory()->for($course)->published()->create();
        Lesson::factory()->for($chapter)->published()->count($count)->create();
    });
});

it('lists the vocabulary notebook in a fixed number of queries', function () {
    assertScales(route('vocabulary.index'), function ($user, int $count) {
        foreach (range(1, $count) as $ignored) {
            $master = Vocabulary::factory()->create();
            SavedVocabulary::create(['user_id' => $user->id, 'term' => $master->term, 'vocabulary_id' => $master->id]);
        }
    });
});

it('lists mistakes in a fixed number of queries', function () {
    assertScales(route('mistakes.index'), function ($user, int $count) {
        $session = startPractice($user, publishedLesson($count));
        foreach (range(1, $count) as $ignored) {
            typeAnswer($user, $session, 'wrong');
        }
    });
});

it('lists notes in a fixed number of queries', function () {
    assertScales(route('notes.index'), function ($user, int $count) {
        $lesson = publishedLesson($count);
        foreach ($lesson->sentences as $sentence) {
            UserNote::create(['user_id' => $user->id, 'sentence_id' => $sentence->id, 'body' => 'note']);
        }
    });
});

it('builds the review center in a fixed number of queries', function () {
    assertScales(route('review.index'), function ($user, int $count) {
        foreach (range(1, $count) as $ignored) {
            $master = Vocabulary::factory()->create();
            $saved = SavedVocabulary::create(['user_id' => $user->id, 'term' => $master->term, 'vocabulary_id' => $master->id]);
            app(ReviewScheduleService::class)->ensureScheduled($user, $saved)->update(['due_at' => now()->subHour()]);
        }
    });
});

it('lists the review history in a fixed number of queries', function () {
    assertScales(route('review.index', ['tab' => 'history']), function ($user, int $count) {
        $service = app(ReviewScheduleService::class);
        foreach (range(1, $count) as $index) {
            $saved = SavedVocabulary::create(['user_id' => $user->id, 'term' => 'word'.uniqid()]);
            $sentence = Sentence::factory()->for(publishedLesson(1))->create();
            $service->recordResult($service->ensureScheduled($user, $saved), true);
            $service->recordResult($service->ensureScheduled($user, $sentence), $index % 2 === 0, 80);
        }
    });
});

it('builds the leaderboard in a fixed number of queries', function () {
    assertScales(route('leaderboard', ['period' => 'all']), function ($user, int $count) {
        foreach (range(1, $count) as $index) {
            $other = learner();
            $other->ensureProfile()->update(['leaderboard_anonymous' => $index % 2 === 0]);
            UserXpTransaction::create(['user_id' => $other->id, 'amount' => 10 + $index, 'action' => 't', 'idempotency_key' => uniqid('k', true), 'awarded_at' => now()]);
        }
    });
});

it('builds the dashboard in a fixed number of queries', function () {
    assertScales(route('dashboard'), function ($user, int $count) {
        foreach (range(1, $count) as $ignored) {
            $session = startPractice($user, publishedLesson(1));
            typeAnswer($user, $session, 'sentence number 1');
        }
    });
});

it('lists admin courses in a fixed number of queries', function () {
    assertScales(route('admin.courses.index'), function ($user, int $count) {
        foreach (range(1, $count) as $ignored) {
            $course = Course::factory()->published()->create();
            Lesson::factory()->for(Chapter::factory()->for($course)->create())->create();
        }
    }, role: 'admin');
});

it('shows an admin course page in a fixed number of queries', function () {
    $course = Course::factory()->create();
    assertScales(route('admin.courses.show', $course), function ($user, int $count) use ($course) {
        foreach (range(1, $count) as $ignored) {
            Lesson::factory()->for(Chapter::factory()->for($course)->create())->count(2)->create();
        }
    }, role: 'admin');
});

it('shows the admin lesson editor in a fixed number of queries', function () {
    $lesson = lessonTree();
    assertScales(route('admin.lessons.show', $lesson), function ($user, int $count) use ($lesson) {
        foreach (Sentence::factory()->for($lesson)->count($count)->create() as $sentence) {
            audioFor($sentence);
            Sentence::factory()->word()->for($lesson)->create(['parent_id' => $sentence->id]);
        }
    }, role: 'admin');
});

it('lists admin categories in a fixed number of queries', function () {
    assertScales(route('admin.categories.index'), function ($user, int $count) {
        Category::factory()->count($count)->create();
    }, role: 'admin');
});

it('lists admin users in a fixed number of queries', function () {
    assertScales(route('admin.users.index'), function ($user, int $count) {
        foreach (range(1, $count) as $ignored) {
            userWithRole('learner');
        }
    }, role: 'admin');
});

it('lists the audio library in a fixed number of queries', function () {
    assertScales(route('admin.audio.index'), function ($user, int $count) {
        foreach (Sentence::factory()->for(lessonTree())->count($count)->create() as $sentence) {
            audioFor($sentence);
        }
    }, role: 'admin');

    assertScales(route('admin.audio.index', ['view' => 'missing']), function ($user, int $count) {
        Sentence::factory()->for(lessonTree())->count($count)->create();
    }, role: 'admin');
});

it('builds admin analytics in a fixed number of queries', function () {
    assertScales(route('admin.analytics'), function ($user, int $count) {
        foreach (range(1, $count) as $ignored) {
            $learner = learner();
            typeAnswer($learner, startPractice($learner, publishedLesson(1)), 'wrong');
        }
    }, role: 'admin');
});
