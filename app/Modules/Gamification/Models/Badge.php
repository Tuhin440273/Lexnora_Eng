<?php

namespace App\Modules\Gamification\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Badge extends Model
{
    use HasUlids;

    protected $fillable = [
        'code', 'name', 'description', 'icon', 'criteria_type',
        'criteria_value', 'xp_reward', 'is_active',
    ];

    protected function casts(): array
    {
        return ['criteria_value' => 'integer', 'xp_reward' => 'integer', 'is_active' => 'boolean'];
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_badges')->withPivot('awarded_at');
    }
}
