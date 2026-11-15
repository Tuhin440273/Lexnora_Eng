<?php

namespace Database\Factories;

use App\Modules\Vocabulary\Models\Vocabulary;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Vocabulary> */
class VocabularyFactory extends Factory
{
    protected $model = Vocabulary::class;

    public function definition(): array
    {
        return [
            'term' => fake()->unique()->word(),
            'type' => 'word',
            'part_of_speech' => 'noun',
            'meaning_en' => fake()->sentence(),
            'meaning_bn' => null,
            'is_active' => true,
        ];
    }
}
