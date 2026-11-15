<?php

namespace App\Modules\Courses\Models;

use App\Modules\Learning\Enums\ContentType;
use App\Modules\Vocabulary\Models\Vocabulary;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Sentence extends Model
{
    use HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'lesson_id', 'parent_id', 'content_type', 'english_text',
        'bengali_text', 'explanation', 'sort_order',
    ];

    protected function casts(): array
    {
        return ['content_type' => ContentType::class, 'sort_order' => 'integer'];
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /** Word / phrase chunks of this sentence, used by the EASY and MEDIUM modes. */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order');
    }

    public function audio(): HasMany
    {
        return $this->hasMany(SentenceAudio::class);
    }

    public function primaryAudio(): HasOne
    {
        return $this->hasOne(SentenceAudio::class)
            ->ofMany(['id' => 'max'], fn (Builder $query) => $query->where('is_primary', true));
    }

    public function vocabulary(): BelongsToMany
    {
        return $this->belongsToMany(Vocabulary::class, 'sentence_vocabulary')->withPivot('position');
    }

    public function scopeTopLevel(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    public function scopeOfType(Builder $query, ContentType $type): Builder
    {
        return $query->where('content_type', $type->value);
    }
}
