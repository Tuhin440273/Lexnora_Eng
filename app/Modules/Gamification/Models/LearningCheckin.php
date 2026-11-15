<?php

namespace App\Modules\Gamification\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LearningCheckin extends Model
{
    use HasUlids;

    protected $fillable = ['user_id', 'checkin_date', 'streak_after', 'xp_awarded'];

    protected function casts(): array
    {
        return ['checkin_date' => 'date', 'streak_after' => 'integer', 'xp_awarded' => 'integer'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
