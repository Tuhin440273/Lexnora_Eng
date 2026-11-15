<?php

namespace App\Modules\Learning;

use App\Models\User;
use App\Modules\Courses\Models\Category;
use App\Modules\Courses\Models\Chapter;
use App\Modules\Courses\Models\Course;
use App\Modules\Courses\Models\Lesson;
use App\Modules\Courses\Models\Sentence;
use App\Modules\Courses\Models\SentenceAudio;
use App\Modules\Courses\Policies\ContentPolicy;
use App\Modules\Learning\Listeners\CreateUserDefaults;
use App\Modules\Vocabulary\Models\SavedVocabulary;
use Illuminate\Auth\Events\Registered;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
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

        $this->registerAuthorization();
    }

    private function registerAuthorization(): void
    {
        // The super-admin passes every check.
        Gate::before(fn (User $user): ?bool => $user->hasRole('super-admin') ? true : null);

        Gate::define('access-admin', fn (User $user): bool => $user->canAccessAdmin());

        foreach ([Category::class, Course::class, Chapter::class, Lesson::class, Sentence::class, SentenceAudio::class] as $model) {
            Gate::policy($model, ContentPolicy::class);
        }
    }
}
