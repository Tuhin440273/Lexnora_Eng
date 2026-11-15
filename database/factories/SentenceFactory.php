<?php

namespace Database\Factories;

use App\Modules\Courses\Models\Lesson;
use App\Modules\Courses\Models\Sentence;
use App\Modules\Learning\Enums\ContentType;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Sentence> */
class SentenceFactory extends Factory
{
    protected $model = Sentence::class;

    public function definition(): array
    {
        return [
            'lesson_id' => Lesson::factory(),
            'parent_id' => null,
            'content_type' => ContentType::Sentence,
            'english_text' => rtrim(fake()->sentence(6), '.').'.',
            'bengali_text' => null,
            'explanation' => null,
            'sort_order' => 0,
        ];
    }

    public function word(): static
    {
        return $this->state(fn () => [
            'content_type' => ContentType::Word,
            'english_text' => fake()->word(),
        ]);
    }

    public function phrase(): static
    {
        return $this->state(fn () => [
            'content_type' => ContentType::Phrase,
            'english_text' => fake()->words(3, true),
        ]);
    }
}
