<?php

use App\Modules\Courses\Models\Category;
use App\Modules\Courses\Models\Chapter;
use App\Modules\Courses\Models\Course;
use App\Modules\Courses\Models\Lesson;
use App\Modules\Courses\Models\Sentence;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

require_once __DIR__.'/Helpers.php';

beforeEach(function () {
    seedRoles();
    $this->withoutVite();
    Storage::fake('local');
});

function validCourse(array $overrides = []): array
{
    return array_merge([
        'title' => 'Everyday English',
        'description' => 'Daily phrases.',
        'cefr_level' => 'A1',
        'difficulty' => 2,
    ], $overrides);
}

it('creates a draft course owned by the creator', function () {
    $user = userWithRole('content-manager');
    $category = Category::factory()->create();

    $response = $this->actingAs($user)
        ->post(route('admin.courses.store'), validCourse(['category_id' => $category->id]));

    $course = Course::firstOrFail();

    $response->assertRedirect(route('admin.courses.show', $course));

    expect($course->slug)->toBe('everyday-english')
        ->and($course->created_by)->toBe($user->id)
        ->and($course->is_published)->toBeFalse()
        ->and($course->category_id)->toBe($category->id)
        ->and($course->sort_order)->toBe(1);
});

it('gives courses with the same title different slugs', function () {
    $this->actingAs(userWithRole('admin'));

    $this->post(route('admin.courses.store'), validCourse());
    $this->post(route('admin.courses.store'), validCourse());

    expect(Course::pluck('slug')->all())->toBe(['everyday-english', 'everyday-english-2']);
});

it('validates the course fields', function (array $bad, string $field) {
    $this->actingAs(userWithRole('admin'))
        ->post(route('admin.courses.store'), validCourse($bad))
        ->assertSessionHasErrors($field);
})->with([
    'missing title' => [['title' => ''], 'title'],
    'unknown level' => [['cefr_level' => 'Z9'], 'cefr_level'],
    'difficulty too high' => [['difficulty' => 9], 'difficulty'],
    'difficulty too low' => [['difficulty' => 0], 'difficulty'],
    'unknown category' => [['category_id' => '01JXXXXXXXXXXXXXXXXXXXXXXX'], 'category_id'],
    'bad slug' => [['slug' => 'Has Spaces'], 'slug'],
]);

it('rejects a thumbnail that is not an image', function () {
    $this->actingAs(userWithRole('admin'))
        ->post(route('admin.courses.store'), validCourse([
            'thumbnail' => UploadedFile::fake()->create('notes.txt', 10, 'text/plain'),
        ]))
        ->assertSessionHasErrors('thumbnail');
});

it('stores a thumbnail and serves it through the app', function () {
    $this->actingAs(userWithRole('admin'))
        ->post(route('admin.courses.store'), validCourse([
            'thumbnail' => UploadedFile::fake()->create('cover.jpg', 100, 'image/jpeg'),
        ]));

    $course = Course::firstOrFail();

    Storage::disk('local')->assertExists($course->thumbnail_path);

    auth()->logout();
    $this->get(route('courses.thumbnail', $course))->assertNotFound();      // draft, guest
    $this->actingAs(userWithRole('learner'))->get(route('courses.thumbnail', $course))->assertNotFound();
    $this->actingAs(userWithRole('admin'))->get(route('courses.thumbnail', $course))->assertOk();

    $course->update(['is_published' => true]);

    auth()->logout();
    $this->get(route('courses.thumbnail', $course))->assertOk();
});

it('replaces and removes a thumbnail', function () {
    $this->actingAs(userWithRole('admin'));
    $this->post(route('admin.courses.store'), validCourse([
        'thumbnail' => UploadedFile::fake()->create('one.jpg', 50, 'image/jpeg'),
    ]));
    $course = Course::firstOrFail();
    $first = $course->thumbnail_path;

    $this->patch(route('admin.courses.update', $course), validCourse([
        'thumbnail' => UploadedFile::fake()->create('two.jpg', 50, 'image/jpeg'),
    ]))->assertSessionHasNoErrors();

    $second = $course->fresh()->thumbnail_path;
    Storage::disk('local')->assertMissing($first);
    Storage::disk('local')->assertExists($second);

    $this->patch(route('admin.courses.update', $course), validCourse(['remove_thumbnail' => '1']));

    expect($course->fresh()->thumbnail_path)->toBeNull();
    Storage::disk('local')->assertMissing($second);
});

it('updates a course without touching its owner or publish state', function () {
    $course = Course::factory()->published()->create(['slug' => 'keep-me']);
    $creator = $course->created_by;

    $this->actingAs(userWithRole('admin'))
        ->patch(route('admin.courses.update', $course), validCourse([
            'title' => 'Renamed',
            'slug' => 'keep-me',
            'cefr_level' => 'B2',
        ]))
        ->assertSessionHasNoErrors();

    $course->refresh();

    expect($course->title)->toBe('Renamed')
        ->and($course->cefr_level->value)->toBe('B2')
        ->and($course->slug)->toBe('keep-me')
        ->and($course->is_published)->toBeTrue()
        ->and($course->created_by)->toBe($creator);
});

it('ignores attempts to set protected fields', function () {
    $user = userWithRole('admin');
    $other = userWithRole('learner');

    $this->actingAs($user)->post(route('admin.courses.store'), validCourse([
        'is_published' => 1,
        'created_by' => $other->id,
        'sort_order' => 99,
    ]));

    $course = Course::firstOrFail();

    expect($course->is_published)->toBeFalse()
        ->and($course->created_by)->toBe($user->id)
        ->and($course->sort_order)->toBe(1);
});

it('lists courses and filters them', function () {
    Course::factory()->published()->create(['title' => 'Alpha Course', 'cefr_level' => 'A1']);
    Course::factory()->create(['title' => 'Beta Course', 'cefr_level' => 'B1']);

    $this->actingAs(userWithRole('admin'));

    $this->get(route('admin.courses.index'))
        ->assertInertia(fn (Assert $page) => $page->component('admin/courses/index')->has('courses.data', 2));

    $this->get(route('admin.courses.index', ['search' => 'Alpha']))
        ->assertInertia(fn (Assert $page) => $page->has('courses.data', 1)->where('courses.data.0.title', 'Alpha Course'));

    $this->get(route('admin.courses.index', ['status' => 'draft']))
        ->assertInertia(fn (Assert $page) => $page->has('courses.data', 1)->where('courses.data.0.title', 'Beta Course'));

    $this->get(route('admin.courses.index', ['cefr_level' => 'B1']))
        ->assertInertia(fn (Assert $page) => $page->has('courses.data', 1));

    $this->get(route('admin.courses.index', ['cefr_level' => 'Z9']))->assertSessionHasErrors('cefr_level');
});

it('treats % and _ in the search box as plain characters', function () {
    Course::factory()->create(['title' => 'Plain title']);

    $this->actingAs(userWithRole('admin'))
        ->get(route('admin.courses.index', ['search' => '%']))
        ->assertInertia(fn (Assert $page) => $page->has('courses.data', 0));
});

it('shows a course with its chapters and lessons in order', function () {
    $course = Course::factory()->create();
    $second = Chapter::factory()->for($course)->create(['title' => 'Second', 'sort_order' => 2]);
    $first = Chapter::factory()->for($course)->create(['title' => 'First', 'sort_order' => 1]);
    Lesson::factory()->for($first)->create(['title' => 'Lesson A', 'sort_order' => 1]);
    Sentence::factory()->for(Lesson::first())->count(3)->create();

    $this->actingAs(userWithRole('admin'))
        ->get(route('admin.courses.show', $course))
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/courses/show')
            ->where('course.chapters.0.title', 'First')
            ->where('course.chapters.1.title', 'Second')
            ->where('course.chapters.0.lessons.0.sentences_count', 3));
});

it('deletes a course together with everything below it', function () {
    $lesson = lessonTree();
    Sentence::factory()->for($lesson)->count(2)->create();
    $course = $lesson->chapter->course;

    $this->actingAs(userWithRole('admin'))
        ->delete(route('admin.courses.destroy', $course))
        ->assertRedirect(route('admin.courses.index'));

    expect(Course::count())->toBe(0)
        ->and(Chapter::count())->toBe(0)
        ->and(Lesson::count())->toBe(0)
        ->and(Sentence::count())->toBe(0)
        ->and(Course::withTrashed()->count())->toBe(1);
});

it('publishes and unpublishes a course', function () {
    $course = Course::factory()->create();
    $this->actingAs(userWithRole('content-manager'));

    $this->patch(route('admin.courses.publish', $course));
    $course->refresh();

    expect($course->is_published)->toBeTrue()
        ->and($course->published_at)->not->toBeNull();

    $firstPublishedAt = $course->published_at;

    $this->patch(route('admin.courses.publish', $course));
    expect($course->fresh()->is_published)->toBeFalse();

    $this->patch(route('admin.courses.publish', $course));
    expect($course->fresh()->published_at->equalTo($firstPublishedAt))->toBeTrue();
});

it('needs the publish permission to publish', function () {
    $user = userWithRole('content-manager');
    Role::findByName('content-manager')->revokePermissionTo('content.publish');
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $course = Course::factory()->create();

    $this->actingAs($user)->patch(route('admin.courses.publish', $course))->assertForbidden();

    expect($course->fresh()->is_published)->toBeFalse();
});

it('forbids learners from every course action', function () {
    $course = Course::factory()->create();
    $this->actingAs(userWithRole('learner'));

    $this->get(route('admin.courses.create'))->assertForbidden();
    $this->post(route('admin.courses.store'), validCourse())->assertForbidden();
    $this->get(route('admin.courses.edit', $course))->assertForbidden();
    $this->patch(route('admin.courses.update', $course), validCourse())->assertForbidden();
    $this->delete(route('admin.courses.destroy', $course))->assertForbidden();
    $this->patch(route('admin.courses.publish', $course))->assertForbidden();

    expect(Course::count())->toBe(1);
});
