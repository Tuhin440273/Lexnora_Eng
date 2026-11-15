<?php

namespace App\Modules\Courses\Policies;

use App\Models\User;

/**
 * One policy for every piece of learning content (categories, courses,
 * chapters, lessons, sentences, audio). It is registered for each model in
 * LearningServiceProvider. Access comes from Spatie permissions; the
 * super-admin role passes every check through Gate::before.
 */
class ContentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('content.manage');
    }

    public function view(User $user, mixed $model = null): bool
    {
        return $user->can('content.manage');
    }

    public function create(User $user, mixed $model = null): bool
    {
        return $user->can('content.manage');
    }

    public function update(User $user, mixed $model = null): bool
    {
        return $user->can('content.manage');
    }

    public function delete(User $user, mixed $model = null): bool
    {
        return $user->can('content.manage');
    }

    public function publish(User $user, mixed $model = null): bool
    {
        return $user->can('content.publish');
    }

    public function manageAudio(User $user, mixed $model = null): bool
    {
        return $user->can('audio.manage');
    }
}
