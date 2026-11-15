<?php

namespace App\Modules\Admin\Http\Resources;

use App\Modules\Courses\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Course */
class CourseResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'description' => $this->description,
            'category_id' => $this->category_id,
            'category' => $this->whenLoaded(
                'category',
                fn (): ?array => $this->category
                    ? ['id' => $this->category->id, 'name' => $this->category->name]
                    : null
            ),
            'cefr_level' => $this->cefr_level->value,
            'cefr_label' => $this->cefr_level->label(),
            'difficulty' => $this->difficulty,
            'is_published' => $this->is_published,
            'published_at' => $this->published_at?->toIso8601String(),
            'sort_order' => $this->sort_order,
            'thumbnail_url' => $this->thumbnail_path
                ? route('courses.thumbnail', ['course' => $this->id])
                : null,
            'chapters_count' => $this->whenCounted('chapters'),
            'lessons_count' => $this->whenCounted('lessons'),
            'estimated_minutes' => $this->when(
                array_key_exists('lessons_sum_estimated_minutes', $this->resource->getAttributes()),
                fn (): int => (int) $this->resource->getAttributes()['lessons_sum_estimated_minutes']
            ),
            'chapters' => $this->whenLoaded(
                'chapters',
                fn (): array => ChapterResource::collection($this->chapters)->resolve()
            ),
        ];
    }
}
