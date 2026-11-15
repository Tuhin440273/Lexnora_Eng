<?php

namespace Database\Factories;

use App\Modules\Courses\Models\Category;
use App\Modules\Courses\Models\Course;
use App\Modules\Learning\Enums\CefrLevel;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Course> */
class CourseFactory extends Factory
{
    protected $model = Course::class;

    public function definition(): array
    {
        $title = Str::title(fake()->unique()->words(3, true));

        return [
            'category_id' => Category::factory(),
            'title' => $title,
            'slug' => Str::slug($title).'-'.Str::lower(Str::random(5)),
            'description' => fake()->paragraph(),
            'cefr_level' => fake()->randomElement(CefrLevel::cases()),
            'difficulty' => fake()->numberBetween(1, 5),
            'is_published' => false,
            'published_at' => null,
            'sort_order' => 0,
        ];
    }

    public function published(): static
    {
        return $this->state(fn () => ['is_published' => true, 'published_at' => now()]);
    }
}
