<?php

namespace App\Modules\Dictation\Models;

use App\Models\User;
use App\Modules\Courses\Models\Sentence;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MistakeRecord extends Model
{
    use HasUlids;

    protected $fillable = [
        'user_id', 'sentence_id', 'latest_attempt_id', 'times_missed', 'last_missed_at', 'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'times_missed' => 'integer',
            'last_missed_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sentence(): BelongsTo
    {
        return $this->belongsTo(Sentence::class);
    }

    public function latestAttempt(): BelongsTo
    {
        return $this->belongsTo(PracticeAttempt::class, 'latest_attempt_id');
    }

    public function scopeUnresolved(Builder $query): Builder
    {
        return $query->whereNull('resolved_at');
    }
}
