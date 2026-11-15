<?php

namespace App\Modules\Review\Models;

use App\Models\User;
use App\Modules\Learning\Enums\ReviewStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

class ReviewSchedule extends Model
{
    use HasUlids;

    protected $fillable = [
        'user_id', 'reviewable_type', 'reviewable_id', 'status', 'step',
        'interval_days', 'repetitions', 'ease_factor', 'due_at', 'last_reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => ReviewStatus::class,
            'step' => 'integer',
            'interval_days' => 'integer',
            'repetitions' => 'integer',
            'ease_factor' => 'decimal:2',
            'due_at' => 'datetime',
            'last_reviewed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** A Sentence or a SavedVocabulary (see the morph map in LearningServiceProvider). */
    public function reviewable(): MorphTo
    {
        return $this->morphTo();
    }

    public function history(): HasMany
    {
        return $this->hasMany(ReviewHistory::class);
    }

    public function scopeDue(Builder $query, ?Carbon $now = null): Builder
    {
        return $query->where('status', ReviewStatus::Active->value)
            ->where('due_at', '<=', $now ?? now());
    }
}
