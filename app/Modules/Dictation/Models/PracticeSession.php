<?php

namespace App\Modules\Dictation\Models;

use App\Models\User;
use App\Modules\Courses\Models\Lesson;
use App\Modules\Learning\Enums\PracticeMode;
use App\Modules\Learning\Enums\SessionStatus;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PracticeSession extends Model
{
    use HasUlids;

    public const KIND_LESSON = 'lesson';

    public const KIND_REVIEW = 'review';

    public const KIND_MISTAKES = 'mistakes';

    protected $fillable = [
        'user_id', 'lesson_id', 'mode', 'kind', 'plan', 'status', 'total_items', 'completed_items',
        'correct_items', 'average_accuracy', 'duration_seconds', 'xp_awarded',
        'started_at', 'last_activity_at', 'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'mode' => PracticeMode::class,
            'status' => SessionStatus::class,
            'plan' => 'array',
            'average_accuracy' => 'decimal:2',
            'started_at' => 'datetime',
            'last_activity_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(PracticeAttempt::class);
    }

    public function isFinished(): bool
    {
        return $this->status !== SessionStatus::InProgress;
    }
}
