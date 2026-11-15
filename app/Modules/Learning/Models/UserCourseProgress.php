<?php

namespace App\Modules\Learning\Models;

use App\Models\User;
use App\Modules\Courses\Models\Course;
use App\Modules\Learning\Enums\ProgressStatus;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserCourseProgress extends Model
{
    use HasUlids;

    protected $table = 'user_course_progress';

    protected $fillable = ['user_id', 'course_id', 'status', 'started_at', 'completed_at', 'last_accessed_at'];

    protected function casts(): array
    {
        return [
            'status' => ProgressStatus::class,
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'last_accessed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }
}
