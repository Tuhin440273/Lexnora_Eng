<?php

use App\Modules\Admin\Exceptions\CannotPublish;
use App\Modules\Admin\Services\PublishingService;
use App\Modules\Admin\Services\SlugService;
use App\Modules\Admin\Services\SortOrderService;
use App\Modules\Courses\Models\Category;
use App\Modules\Courses\Models\Chapter;
use App\Modules\Courses\Models\Course;
use App\Modules\Courses\Models\Lesson;

require_once __DIR__.'/Helpers.php';

it('builds slugs from titles', function (string $title, string $expected) {
    expect(app(SlugService::class)->unique(Course::class, $title, null, 170))->toBe($expected);
})->with([
    ['Hello World', 'hello-world'],
    ['  Many   Spaces  ', 'many-spaces'],
    ['Café & Bar!', 'cafe-bar'],
    ['বাংলা', 'bangla'],
    ['!!!', 'item'],
    ['', 'item'],
]);

it('counts soft-deleted rows when making a slug unique', function () {
    Category::factory()->create(['slug' => 'travel'])->delete();

    expect(app(SlugService::class)->unique(Category::class, 'Travel'))->toBe('travel-2');
});

it('lets a row keep its own slug', function () {
    $category = Category::factory()->create(['slug' => 'travel']);

    expect(app(SlugService::class)->unique(Category::class, 'Travel', $category->id))->toBe('travel');
});

it('keeps slugs inside the column length', function () {
    $slug = app(SlugService::class)->unique(Category::class, str_repeat('word ', 100), null, 120);

    expect(strlen($slug))->toBeLessThanOrEqual(120);
});

it('moves rows without touching the order of other groups', function () {
    $service = app(SortOrderService::class);
    $chapters = Chapter::factory()->count(3)->create(['course_id' => Course::factory()->create()->id])
        ->each(fn ($chapter, $index) => $chapter->update(['sort_order' => $index + 1]));
    $query = fn () => Chapter::where('course_id', $chapters->first()->course_id);

    $service->move($chapters[0], $query(), 'down');
    expect($query()->orderBy('sort_order')->pluck('id')->all())
        ->toBe([$chapters[1]->id, $chapters[0]->id, $chapters[2]->id]);

    $service->move($chapters[2], $query(), 'down');   // last: no change
    expect($query()->orderBy('sort_order')->pluck('id')->all())
        ->toBe([$chapters[1]->id, $chapters[0]->id, $chapters[2]->id]);
});

it('returns the next free position', function () {
    $service = app(SortOrderService::class);
    $course = Course::factory()->create();

    expect($service->next(Chapter::where('course_id', $course->id)))->toBe(1);

    Chapter::factory()->for($course)->create(['sort_order' => 4]);

    expect($service->next(Chapter::where('course_id', $course->id)))->toBe(5);
});

it('refuses to publish an empty lesson', function () {
    expect(fn () => app(PublishingService::class)->toggle(lessonTree()))
        ->toThrow(CannotPublish::class, 'Add at least one sentence');
});

it('can always unpublish', function () {
    $lesson = Lesson::factory()->published()->create();

    expect(app(PublishingService::class)->toggle($lesson))->toBeFalse();
});
