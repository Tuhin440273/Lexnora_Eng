<?php

namespace App\Modules\Dictation\Models;

use App\Models\User;
use App\Modules\Courses\Models\Sentence;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PracticeAttempt extends Model
{
    use HasUlids;

    protected $fillable = [
        'practice_session_id', 'user_id', 'sentence_id', 'attempt_number',
        'submitted_text', 'expected_text', 'accuracy', 'is_correct', 'error_details',
        'hints_used', 'replay_count', 'playback_speed', 'time_spent_ms', 'answered_at',
    ];

    protected function casts(): array
    {
        return [
            'accuracy' => 'decimal:2',
            'is_correct' => 'boolean',
            'error_details' => 'array',
            'playback_speed' => 'decimal:2',
            'answered_at' => 'datetime',
        ];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(PracticeSession::class, 'practice_session_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sentence(): BelongsTo
    {
        return $this->belongsTo(Sentence::class);
    }
}
