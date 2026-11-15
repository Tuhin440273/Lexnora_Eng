<?php

use App\Modules\Courses\Models\Chapter;
use App\Modules\Courses\Models\Course;
use App\Modules\Courses\Models\Lesson;
use App\Modules\Courses\Models\Sentence;
use Inertia\Testing\AssertableInertia as Assert;

require_once __DIR__.'/Helpers.php';

beforeEach(function () {
    seedRoles();
    $this->withoutVite();
});

it('adds chapters to the end of a course', function () {
    $course = Course::factory()->create();
    $this->actingAs(userWithRole('content-manager'));

    $this->post(route('admin.chapters.store', $course), ['title' => 'One'])->assertSessionHasNoErrors();
    $this->post(route('admin.chapters.store', $course), ['title' => 'Two']);

    expect($course->chapters()->pluck('title')->all())->toBe(['One', 'Two'])
        ->and($course->chapters()->pluck('sort_order')->all())->toBe([1, 2])
        ->and($course->chapters->every(fn ($chapter) => $chapter->is_published === false))->toBeTrue();
});

it('numbers chapters per course', function () {
    $other = Chapter::factory()->create(['sort_order' => 7]);
    $course = Course::factory()->create();
    $this->actingAs(userWithRole('admin'));

    $this->post(route('admin.chapters.store', $course), ['title' => 'First']);

    expect($course->chapters()->first()->sort_order)->toBe(1)
        ->and($other->fresh()->sort_order)->toBe(7);
});

it('requires a chapter title', function () {
    $course = Course::factory()->create();

    $this->actingAs(userWithRole('admin'))
        ->post(route('admin.chapters.store', $course), ['title' => ''])
        ->assertSessionHasErrors('title');
});

it('updates and deletes a chapter with its lessons', function () {
    $lesson = lessonTree();
    $chapter = $lesson->chapter;
    Sentence::factory()->for($lesson)->create();
    $this->actingAs(userWithRole('admin'));

    $this->patch(route('admin.chapters.update', $chapter), ['title' => 'Renamed'])->assertSessionHasNoErrors();
    expect($chapter->fresh()->title)->toBe('Renamed');

    $this->delete(route('admin.chapters.destroy', $chapter));

    expect(Chapter::count())->toBe(0)
        ->and(Lesson::count())->toBe(0)
        ->and(Sentence::count())->toBe(0);
});

it('moves chapters up and down', function () {
    $course = Course::factory()->create();
    $a = Chapter::factory()->for($course)->create(['title' => 'A', 'sort_order' => 1]);
    $b = Chapter::factory()->for($course)->create(['title' => 'B', 'sort_order' => 2]);
    $c = Chapter::factory()->for($course)->create(['title' => 'C', 'sort_order' => 3]);
    $this->actingAs(userWithRole('admin'));

    $this->patch(route('admin.chapters.move', $c), ['direction' => 'up']);
    expect($course->chapters()->pluck('title')->all())->toBe(['A', 'C', 'B']);

    $this->patch(route('admin.chapters.move', $a), ['direction' => 'down']);
    expect($course->chapters()->pluck('title')->all())->toBe(['C', 'A', 'B']);

    $this->patch(route('admin.chapters.move', $c), ['direction' => 'up']);   // already first
    expect($course->chapters()->pluck('title')->all())->toBe(['C', 'A', 'B']);
});

it('repairs duplicate positions when moving', function () {
    $course = Course::factory()->create();
    Chapter::factory()->for($course)->create(['title' => 'A', 'sort_order' => 0]);
    $b = Chapter::factory()->for($course)->create(['title' => 'B', 'sort_order' => 0]);
    $this->actingAs(userWithRole('admin'));

    $this->patch(route('admin.chapters.move', $b), ['direction' => 'up']);

    expect($course->chapters()->pluck('title')->all())->toBe(['B', 'A'])
        ->and($course->chapters()->pluck('sort_order')->all())->toBe([1, 2]);
});

it('only accepts up or down', function () {
    $chapter = Chapter::factory()->create();

    $this->actingAs(userWithRole('admin'))
        ->patch(route('admin.chapters.move', $chapter), ['direction' => 'sideways'])
        ->assertSessionHasErrors('direction');
});

it('adds, edits and deletes a lesson', function () {
    $chapter = Chapter::factory()->create();
    $this->actingAs(userWithRole('content-manager'));

    $this->post(route('admin.lessons.store', $chapter), [
        'title' => 'Saying Hello', 'difficulty' => 1, 'estimated_minutes' => 5,
    ])->assertSessionHasNoErrors();

    $lesson = Lesson::firstOrFail();
    expect($lesson->chapter_id)->toBe($chapter->id)
        ->and($lesson->sort_order)->toBe(1)
        ->and($lesson->is_published)->toBeFalse();

    $this->patch(route('admin.lessons.update', $lesson), [
        'title' => 'Greetings', 'difficulty' => 3, 'estimated_minutes' => 12,
    ]);
    expect($lesson->fresh()->title)->toBe('Greetings')
        ->and($lesson->fresh()->estimated_minutes)->toBe(12);

    $this->delete(route('admin.lessons.destroy', $lesson))
        ->assertRedirect(route('admin.courses.show', $chapter->course_id));
    expect(Lesson::count())->toBe(0);
});

it('validates lesson fields', function (array $bad, string $field) {
    $chapter = Chapter::factory()->create();
    $data = array_merge(['title' => 'Ok', 'difficulty' => 1, 'estimated_minutes' => 5], $bad);

    $this->actingAs(userWithRole('admin'))
        ->post(route('admin.lessons.store', $chapter), $data)
        ->assertSessionHasErrors($field);
})->with([
    'no title' => [['title' => ''], 'title'],
    'difficulty 6' => [['difficulty' => 6], 'difficulty'],
    'zero minutes' => [['estimated_minutes' => 0], 'estimated_minutes'],
    'minutes too long' => [['estimated_minutes' => 999], 'estimated_minutes'],
]);

it('moves lessons inside their chapter only', function () {
    $chapter = Chapter::factory()->create();
    $a = Lesson::factory()->for($chapter)->create(['title' => 'A', 'sort_order' => 1]);
    $b = Lesson::factory()->for($chapter)->create(['title' => 'B', 'sort_order' => 2]);
    $elsewhere = Lesson::factory()->create(['sort_order' => 1]);
    $this->actingAs(userWithRole('admin'));

    $this->patch(route('admin.lessons.move', $b), ['direction' => 'up']);

    expect($chapter->lessons()->pluck('title')->all())->toBe(['B', 'A'])
        ->and($elsewhere->fresh()->sort_order)->toBe(1);
});

it('will not publish a lesson without sentences', function () {
    $lesson = lessonTree();
    $this->actingAs(userWithRole('admin'));

    $this->patch(route('admin.lessons.publish', $lesson));

    expect($lesson->fresh()->is_published)->toBeFalse();
});

it('publishes a lesson once it has a sentence', function () {
    $lesson = lessonTree();
    Sentence::factory()->for($lesson)->create();
    $this->actingAs(userWithRole('admin'));

    $this->patch(route('admin.lessons.publish', $lesson));
    expect($lesson->fresh()->is_published)->toBeTrue();

    $this->patch(route('admin.lessons.publish', $lesson));
    expect($lesson->fresh()->is_published)->toBeFalse();
});

it('counts a standalone word as lesson content when publishing', function () {
    $lesson = lessonTree();
    Sentence::factory()->word()->for($lesson)->create();
    $this->actingAs(userWithRole('admin'));

    $this->patch(route('admin.lessons.publish', $lesson));

    expect($lesson->fresh()->is_published)->toBeTrue();
});

it('publishes and unpublishes a chapter', function () {
    $chapter = Chapter::factory()->create();
    $this->actingAs(userWithRole('admin'));

    $this->patch(route('admin.chapters.publish', $chapter));
    expect($chapter->fresh()->is_published)->toBeTrue();

    $this->patch(route('admin.chapters.publish', $chapter));
    expect($chapter->fresh()->is_published)->toBeFalse();
});

it('shows the lesson editor with sentences, chunks and audio', function () {
    $lesson = lessonTree();
    $sentence = Sentence::factory()->for($lesson)->create(['sort_order' => 1]);
    Sentence::factory()->word()->for($lesson)->create(['parent_id' => $sentence->id]);
    audioFor($sentence);

    $this->actingAs(userWithRole('admin'))
        ->get(route('admin.lessons.show', $lesson))
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/lessons/show')
            ->has('sentences', 1)
            ->has('sentences.0.children', 1)
            ->has('sentences.0.audio', 1)
            ->where('sentences.0.audio.0.is_primary', true)
            ->where('course.id', $lesson->chapter->course_id));
});

it('forbids learners from chapter and lesson actions', function () {
    $lesson = lessonTree();
    $chapter = $lesson->chapter;
    $this->actingAs(userWithRole('learner'));

    $this->post(route('admin.chapters.store', $chapter->course), ['title' => 'X'])->assertForbidden();
    $this->patch(route('admin.chapters.update', $chapter), ['title' => 'X'])->assertForbidden();
    $this->delete(route('admin.chapters.destroy', $chapter))->assertForbidden();
    $this->post(route('admin.lessons.store', $chapter), ['title' => 'X', 'difficulty' => 1, 'estimated_minutes' => 5])->assertForbidden();
    $this->get(route('admin.lessons.show', $lesson))->assertForbidden();
    $this->patch(route('admin.lessons.publish', $lesson))->assertForbidden();
});
