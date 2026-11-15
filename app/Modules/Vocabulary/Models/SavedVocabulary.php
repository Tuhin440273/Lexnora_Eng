<?php

namespace App\Modules\Vocabulary\Models;

use App\Models\User;
use App\Modules\Courses\Models\Sentence;
use App\Modules\Learning\Concerns\NormalizesTerm;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/** A word or phrase a learner saved to their own notebook. */
class SavedVocabulary extends Model
{
    use HasUlids, NormalizesTerm, SoftDeletes;

    protected $table = 'saved_vocabulary';

    protected $fillable = [
        'user_id', 'vocabulary_id', 'context_sentence_id', 'term', 'type',
        'context_text', 'personal_meaning', 'notes', 'is_learned', 'learned_at',
    ];

    protected function casts(): array
    {
        return ['is_learned' => 'boolean', 'learned_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function vocabulary(): BelongsTo
    {
        return $this->belongsTo(Vocabulary::class);
    }

    public function contextSentence(): BelongsTo
    {
        return $this->belongsTo(Sentence::class, 'context_sentence_id');
    }

    public function scopeLearned(Builder $query): Builder
    {
        return $query->where('is_learned', true);
    }

    public function scopeSearch(Builder $query, string $term): Builder
    {
        $like = '%'.static::normalizeTerm($term).'%';

        return $query->where(fn (Builder $q) => $q
            ->where('normalized_term', 'like', $like)
            ->orWhere('personal_meaning', 'like', $like));
    }
}
