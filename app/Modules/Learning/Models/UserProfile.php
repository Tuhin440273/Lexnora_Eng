<?php

namespace App\Modules\Learning\Models;

use App\Models\User;
use App\Modules\Learning\Enums\CefrLevel;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserProfile extends Model
{
    use HasFactory, HasUlids;

    protected $table = 'user_profiles';

    protected $fillable = [
        'user_id',
        'display_name',
        'avatar_path',
        'native_language',
        'target_cefr_level',
        'daily_goal_minutes',
        'daily_goal_sentences',
        'timezone',
        'leaderboard_anonymous',
        'preferences',
    ];

    protected function casts(): array
    {
        return [
            'target_cefr_level' => CefrLevel::class,
            'daily_goal_minutes' => 'integer',
            'daily_goal_sentences' => 'integer',
            'leaderboard_anonymous' => 'boolean',
            'preferences' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The saved preferences (default mode, speed, language...), always as an array.
     *
     * @return array<string, mixed>
     */
    public function preferenceList(): array
    {
        $value = $this->getAttribute('preferences');

        return is_array($value) ? $value : [];
    }
}
