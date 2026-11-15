<?php

namespace App\Modules\Admin\Http\Resources;

use App\Modules\Courses\Models\Chapter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Chapter */
class ChapterResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'course_id' => $this->course_id,
            'title' => $this->title,
            'description' => $this->description,
            'sort_order' => $this->sort_order,
            'is_published' => $this->is_published,
            'lessons' => $this->whenLoaded(
                'lessons',
                fn (): array => LessonResource::collection($this->lessons)->resolve()
            ),
        ];
    }
}
