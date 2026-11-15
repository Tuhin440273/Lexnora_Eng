<?php

namespace App\Modules\Courses\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lesson extends Model
{
    use HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'chapter_id', 'title', 'description', 'difficulty',
        'estimated_minutes', 'sort_order', 'is_published',
    ];

    protected function casts(): array
    {
        return [
            'difficulty' => 'integer',
            'estimated_minutes' => 'integer',
            'sort_order' => 'integer',
            'is_published' => 'boolean',
        ];
    }

    public function chapter(): BelongsTo
    {
        return $this->belongsTo(Chapter::class);
    }

    /** Top-level sentences only (word/phrase chunks hang off their parent sentence). */
    public function sentences(): HasMany
    {
        return $this->hasMany(Sentence::class)->whereNull('parent_id')->orderBy('sort_order');
    }

    /** Every row of the lesson: sentences plus their word/phrase chunks. */
    public function allSentences(): HasMany
    {
        return $this->hasMany(Sentence::class)->orderBy('sort_order');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    /**
     * A lesson is visible to learners only when it AND its chapter AND its
     * course are all published.
     */
    public function scopeLearnerVisible(Builder $query): Builder
    {
        return $query->where('lessons.is_published', true)
            ->whereHas('chapter', fn (Builder $chapter) => $chapter
                ->where('is_published', true)
                ->whereHas('course', fn (Builder $course) => $course->where('is_published', true)));
    }
}
