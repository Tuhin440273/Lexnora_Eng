<?php

namespace App\Modules\Review\Models;

use App\Models\User;
use App\Modules\Dictation\Models\PracticeAttempt;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReviewHistory extends Model
{
    use HasUlids;

    protected $table = 'review_history';

    protected $fillable = [
        'review_schedule_id', 'user_id', 'practice_attempt_id', 'is_successful',
        'accuracy', 'previous_step', 'new_step', 'was_due_at', 'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'is_successful' => 'boolean',
            'accuracy' => 'decimal:2',
            'previous_step' => 'integer',
            'new_step' => 'integer',
            'was_due_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(ReviewSchedule::class, 'review_schedule_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(PracticeAttempt::class, 'practice_attempt_id');
    }
}
