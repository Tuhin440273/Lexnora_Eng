<?php

namespace App\Modules\Analytics\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyLearningStatistic extends Model
{
    use HasUlids;

    protected $fillable = [
        'user_id', 'stat_date', 'study_seconds', 'sessions_count', 'attempts_count',
        'correct_attempts', 'accuracy_total', 'lessons_completed', 'xp_earned',
    ];

    protected function casts(): array
    {
        return [
            'stat_date' => 'date',
            'accuracy_total' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Average accuracy for the day, derived from the stored sum. */
    public function averageAccuracy(): ?float
    {
        return $this->attempts_count > 0
            ? round((float) $this->accuracy_total / $this->attempts_count, 2)
            : null;
    }
}
