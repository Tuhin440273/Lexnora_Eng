<?php

namespace App\Modules\Learning\Models;

use App\Models\User;
use App\Modules\Courses\Models\Lesson;
use App\Modules\Learning\Enums\PracticeMode;
use App\Modules\Learning\Enums\ProgressStatus;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserLessonProgress extends Model
{
    use HasUlids;

    protected $table = 'user_lesson_progress';

    protected $fillable = [
        'user_id', 'lesson_id', 'status', 'last_mode', 'best_accuracy',
        'sessions_count', 'last_practiced_at', 'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => ProgressStatus::class,
            'last_mode' => PracticeMode::class,
            'best_accuracy' => 'decimal:2',
            'sessions_count' => 'integer',
            'last_practiced_at' => 'datetime',
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
}
