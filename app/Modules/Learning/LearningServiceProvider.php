<?php

namespace App\Modules\Learning;

use App\Modules\Courses\Models\Sentence;
use App\Modules\Learning\Listeners\CreateUserDefaults;
use App\Modules\Vocabulary\Models\SavedVocabulary;
use Illuminate\Auth\Events\Registered;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class LearningServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Short, stable names for polymorphic columns (review_schedules.reviewable_type).
        // Non-enforcing on purpose: Spatie and Laravel keep using class names for users.
        Relation::morphMap([
            'sentence' => Sentence::class,
            'saved_vocabulary' => SavedVocabulary::class,
        ]);

        // Models live in app/Modules/*, factories stay in database/factories.
        Factory::guessFactoryNamesUsing(
            fn (string $modelName): string => 'Database\\Factories\\'.class_basename($modelName).'Factory'
        );

        // Every new account gets a profile and the default "learner" role.
        Event::listen(Registered::class, CreateUserDefaults::class);
    }
}
