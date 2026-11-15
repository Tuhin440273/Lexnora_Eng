<?php

namespace App\Modules\Learning\Models;

use App\Models\User;
use App\Modules\Courses\Models\Sentence;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserNote extends Model
{
    use HasUlids, SoftDeletes;

    protected $table = 'user_notes';

    protected $fillable = ['user_id', 'sentence_id', 'title', 'body'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sentence(): BelongsTo
    {
        return $this->belongsTo(Sentence::class);
    }
}
