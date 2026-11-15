<?php

namespace App\Modules\Admin\Http\Resources;

use App\Modules\Courses\Models\Sentence;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Sentence */
class SentenceResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'lesson_id' => $this->lesson_id,
            'parent_id' => $this->parent_id,
            'content_type' => $this->content_type->value,
            'english_text' => $this->english_text,
            'bengali_text' => $this->bengali_text,
            'explanation' => $this->explanation,
            'sort_order' => $this->sort_order,
            'audio' => $this->whenLoaded(
                'audio',
                fn (): array => AudioResource::collection($this->audio)->resolve()
            ),
            'children' => $this->whenLoaded(
                'children',
                fn (): array => self::collection($this->children)->resolve()
            ),
        ];
    }
}
