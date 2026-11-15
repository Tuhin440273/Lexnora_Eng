<?php

namespace App\Modules\Learning\Concerns;

use App\Modules\Dictation\Models\MistakeRecord;
use App\Modules\Dictation\Models\PracticeSession;
use App\Modules\Gamification\Models\Badge;
use App\Modules\Gamification\Models\UserXpTransaction;
use App\Modules\Learning\Models\UserNote;
use App\Modules\Learning\Models\UserProfile;
use App\Modules\Review\Models\ReviewSchedule;
use App\Modules\Vocabulary\Models\SavedVocabulary;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Added to App\Models\User (together with Spatie's HasRoles) by phase2-setup.sh.
 * Keeps all learning-related relationships out of the framework User class.
 */
trait HasLearningProfile
{
    /** Lets the users table's `deactivated_at` behave as a date. */
    public function initializeHasLearningProfile(): void
    {
        $this->mergeCasts(['deactivated_at' => 'datetime']);
    }

    public function isDeactivated(): bool
    {
        return $this->getAttribute('deactivated_at') !== null;
    }

    public function profile(): HasOne
    {
        return $this->hasOne(UserProfile::class);
    }

    public function practiceSessions(): HasMany
    {
        return $this->hasMany(PracticeSession::class);
    }

    public function savedVocabulary(): HasMany
    {
        return $this->hasMany(SavedVocabulary::class);
    }

    public function mistakeRecords(): HasMany
    {
        return $this->hasMany(MistakeRecord::class);
    }

    public function notes(): HasMany
    {
        return $this->hasMany(UserNote::class);
    }

    public function reviewSchedules(): HasMany
    {
        return $this->hasMany(ReviewSchedule::class);
    }

    public function xpTransactions(): HasMany
    {
        return $this->hasMany(UserXpTransaction::class);
    }

    public function badges(): BelongsToMany
    {
        return $this->belongsToMany(Badge::class, 'user_badges')->withPivot('awarded_at');
    }

    /** Returns the profile, creating it with defaults when missing. */
    public function ensureProfile(): UserProfile
    {
        $profile = $this->profile()->firstOrCreate([], []);

        // A new row only holds what was inserted; reload it to get the database defaults.
        return $profile->wasRecentlyCreated ? $profile->fresh() : $profile;
    }

    public function isAdmin(): bool
    {
        return $this->hasAnyRole(['super-admin', 'admin']);
    }

    /** Anyone who may open the admin area (admins and content managers). */
    public function canAccessAdmin(): bool
    {
        return $this->hasAnyRole(['super-admin', 'admin', 'content-manager']);
    }
}
