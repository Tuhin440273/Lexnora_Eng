<?php

namespace App\Modules\Courses\Models;

use App\Models\User;
use App\Modules\Learning\Enums\CefrLevel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

class Course extends Model
{
    use HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'category_id', 'created_by', 'title', 'slug', 'description', 'thumbnail_path',
        'cefr_level', 'difficulty', 'is_published', 'published_at', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'cefr_level' => CefrLevel::class,
            'difficulty' => 'integer',
            'is_published' => 'boolean',
            'published_at' => 'datetime',
            'sort_order' => 'integer',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function chapters(): HasMany
    {
        return $this->hasMany(Chapter::class)->orderBy('sort_order');
    }

    public function lessons(): HasManyThrough
    {
        return $this->hasManyThrough(Lesson::class, Chapter::class);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    /**
     * Estimated duration is derived from the lessons, never stored twice.
     * For lists, prefer ->withSum('lessons', 'estimated_minutes') to avoid N+1.
     */
    protected function estimatedDurationMinutes(): Attribute
    {
        return Attribute::get(function (): int {
            if (array_key_exists('lessons_sum_estimated_minutes', $this->attributes)) {
                return (int) $this->attributes['lessons_sum_estimated_minutes'];
            }

            return (int) $this->lessons()->sum('lessons.estimated_minutes');
        });
    }
}
