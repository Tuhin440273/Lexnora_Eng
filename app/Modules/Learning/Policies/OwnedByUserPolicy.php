<?php

namespace App\Modules\Learning\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Private learner records (sessions, notes, saved words, mistakes, review
 * items) belong to one user and nobody else, not even staff.
 */
class OwnedByUserPolicy
{
    public function view(User $user, Model $record): bool
    {
        return $record->getAttribute('user_id') === $user->id;
    }

    public function update(User $user, Model $record): bool
    {
        return $this->view($user, $record);
    }

    public function delete(User $user, Model $record): bool
    {
        return $this->view($user, $record);
    }
}
