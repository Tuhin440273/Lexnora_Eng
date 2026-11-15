<?php

namespace App\Modules\Courses\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SentenceAudio extends Model
{
    use HasUlids, SoftDeletes;

    protected $table = 'sentence_audio';

    protected $fillable = [
        'sentence_id', 'uploaded_by', 'disk', 'path', 'original_name',
        'mime_type', 'size_bytes', 'duration_ms', 'voice_label', 'is_primary',
    ];

    protected function casts(): array
    {
        return [
            'size_bytes' => 'integer',
            'duration_ms' => 'integer',
            'is_primary' => 'boolean',
        ];
    }

    public function sentence(): BelongsTo
    {
        return $this->belongsTo(Sentence::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Audio is streamed through the app (permission checked), never linked directly.
     */
    public function url(): string
    {
        return route('audio.stream', ['audio' => $this->id]);
    }
}
