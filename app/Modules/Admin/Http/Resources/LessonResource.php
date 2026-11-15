<?php

namespace App\Modules\Admin\Http\Resources;

use App\Modules\Courses\Models\Lesson;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Lesson */
class LessonResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'chapter_id' => $this->chapter_id,
            'title' => $this->title,
            'description' => $this->description,
            'difficulty' => $this->difficulty,
            'estimated_minutes' => $this->estimated_minutes,
            'sort_order' => $this->sort_order,
            'is_published' => $this->is_published,
            'sentences_count' => $this->whenCounted('sentences'),
        ];
    }
}
