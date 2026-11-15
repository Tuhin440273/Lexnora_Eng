<?php

use App\Modules\Courses\Models\Category;
use App\Modules\Courses\Models\Course;
use Inertia\Testing\AssertableInertia as Assert;

require_once __DIR__.'/Helpers.php';

beforeEach(function () {
    seedRoles();
    $this->withoutVite();
});

it('lists categories with their course counts', function () {
    $category = Category::factory()->create();
    Course::factory()->count(2)->create(['category_id' => $category->id]);

    $this->actingAs(userWithRole('content-manager'))
        ->get(route('admin.categories.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/categories/index')
            ->where('categories.0.courses_count', 2));
});

it('creates a category and builds the slug from the name', function () {
    $this->actingAs(userWithRole('content-manager'))
        ->post(route('admin.categories.store'), ['name' => 'Daily Life', 'is_active' => '1'])
        ->assertRedirect();

    $category = Category::firstOrFail();

    expect($category->slug)->toBe('daily-life')
        ->and($category->is_active)->toBeTrue()
        ->and($category->sort_order)->toBe(1);
});

it('adds a number to a slug that already exists', function () {
    Category::factory()->create(['slug' => 'travel']);

    $this->actingAs(userWithRole('admin'))
        ->post(route('admin.categories.store'), ['name' => 'Travel']);

    expect(Category::pluck('slug')->all())->toContain('travel', 'travel-2');
});

it('rejects a duplicate or malformed slug', function () {
    Category::factory()->create(['slug' => 'travel']);
    $this->actingAs(userWithRole('admin'));

    $this->post(route('admin.categories.store'), ['name' => 'X', 'slug' => 'travel'])
        ->assertSessionHasErrors('slug');
    $this->post(route('admin.categories.store'), ['name' => 'X', 'slug' => 'Not A Slug!'])
        ->assertSessionHasErrors('slug');
});

it('requires a name', function () {
    $this->actingAs(userWithRole('admin'))
        ->post(route('admin.categories.store'), ['name' => ''])
        ->assertSessionHasErrors('name');
});

it('updates a category and keeps its own slug valid', function () {
    $category = Category::factory()->create(['name' => 'Old', 'slug' => 'old']);

    $this->actingAs(userWithRole('admin'))
        ->patch(route('admin.categories.update', $category), ['name' => 'New', 'slug' => 'old', 'is_active' => '1'])
        ->assertSessionHasNoErrors();

    expect($category->fresh()->name)->toBe('New');
});

it('can switch a category off', function () {
    $category = Category::factory()->create(['is_active' => true]);

    $this->actingAs(userWithRole('admin'))
        ->patch(route('admin.categories.update', $category), ['name' => $category->name]);

    expect($category->fresh()->is_active)->toBeFalse();
});

it('does not delete a category that still has courses', function () {
    $category = Category::factory()->create();
    Course::factory()->create(['category_id' => $category->id]);

    $this->actingAs(userWithRole('admin'))
        ->delete(route('admin.categories.destroy', $category));

    expect(Category::count())->toBe(1);
});

it('deletes an empty category', function () {
    $category = Category::factory()->create();

    $this->actingAs(userWithRole('admin'))
        ->delete(route('admin.categories.destroy', $category));

    expect(Category::count())->toBe(0)
        ->and(Category::withTrashed()->count())->toBe(1);
});

it('forbids learners from changing categories', function () {
    $category = Category::factory()->create();
    $this->actingAs(userWithRole('learner'));

    $this->post(route('admin.categories.store'), ['name' => 'X'])->assertForbidden();
    $this->patch(route('admin.categories.update', $category), ['name' => 'X'])->assertForbidden();
    $this->delete(route('admin.categories.destroy', $category))->assertForbidden();
});
