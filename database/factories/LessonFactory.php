<?php

namespace Database\Factories;

use App\Modules\Courses\Models\Chapter;
use App\Modules\Courses\Models\Lesson;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Lesson> */
class LessonFactory extends Factory
{
    protected $model = Lesson::class;

    public function definition(): array
    {
        return [
            'chapter_id' => Chapter::factory(),
            'title' => Str::title(fake()->words(3, true)),
            'description' => fake()->sentence(),
            'difficulty' => fake()->numberBetween(1, 5),
            'estimated_minutes' => fake()->numberBetween(3, 15),
            'sort_order' => 0,
            'is_published' => false,
        ];
    }

    public function published(): static
    {
        return $this->state(fn () => ['is_published' => true]);
    }
}
