<?php

namespace App\Modules\Admin\Http\Resources;

use App\Modules\Courses\Models\SentenceAudio;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin SentenceAudio */
class AudioResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sentence_id' => $this->sentence_id,
            'url' => $this->url(),
            'original_name' => $this->original_name,
            'mime_type' => $this->mime_type,
            'size_bytes' => $this->size_bytes,
            'duration_ms' => $this->duration_ms,
            'voice_label' => $this->voice_label,
            'is_primary' => $this->is_primary,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
