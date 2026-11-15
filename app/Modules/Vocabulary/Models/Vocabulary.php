<?php

namespace App\Modules\Vocabulary\Models;

use App\Models\User;
use App\Modules\Courses\Models\Sentence;
use App\Modules\Learning\Concerns\NormalizesTerm;
use App\Modules\Learning\Enums\CefrLevel;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/** Master (admin-managed) dictionary entry. Users save their own copy in SavedVocabulary. */
class Vocabulary extends Model
{
    use HasFactory, HasUlids, NormalizesTerm;

    protected $table = 'vocabulary';

    protected $fillable = [
        'created_by', 'term', 'type', 'part_of_speech', 'meaning_en',
        'meaning_bn', 'pronunciation', 'example', 'cefr_level', 'is_active',
    ];

    protected function casts(): array
    {
        return ['cefr_level' => CefrLevel::class, 'is_active' => 'boolean'];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function sentences(): BelongsToMany
    {
        return $this->belongsToMany(Sentence::class, 'sentence_vocabulary')->withPivot('position');
    }
}
