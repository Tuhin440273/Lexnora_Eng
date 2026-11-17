<?php

use App\Modules\Courses\Models\Course;
use App\Modules\Learning\Enums\CefrLevel;
use Inertia\Testing\AssertableInertia as Assert;

require_once __DIR__.'/../Phase4/Helpers.php';

beforeEach(function () {
    seedRoles();
    $this->withoutVite();
});

it('gives every CEFR level a lower-case key for the accent colour', function () {
    expect(CefrLevel::A1->key())->toBe('a1')
        ->and(CefrLevel::B2->key())->toBe('b2')
        ->and(CefrLevel::C1->key())->toBe('c1');
});

it('sends the level key with the course page', function () {
    $course = Course::factory()->published()->create(['cefr_level' => CefrLevel::A2]);

    $this->actingAs(learner())
        ->get(route('courses.show', $course))
        ->assertInertia(fn (Assert $page) => $page
            ->component('courses/show')
            ->where('course.cefr', 'a2')
            ->where('course.cefr_label', 'A2 — Elementary'));
});
