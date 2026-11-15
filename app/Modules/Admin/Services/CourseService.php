<?php

namespace App\Modules\Admin\Services;

use App\Models\User;
use App\Modules\Courses\Models\Course;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CourseService
{
    public function __construct(
        private readonly SlugService $slugs,
        private readonly SortOrderService $order,
    ) {}

    /**
     * @param  array<string, mixed>  $data  Validated course fields.
     */
    public function create(array $data, ?UploadedFile $thumbnail, User $creator): Course
    {
        return DB::transaction(function () use ($data, $thumbnail, $creator): Course {
            $course = new Course(Arr::only($data, [
                'category_id', 'title', 'description', 'cefr_level', 'difficulty',
            ]));

            $course->slug = $data['slug'] ?? $this->slugs->unique(Course::class, $data['title'], null, 170);
            $course->created_by = $creator->id;
            $course->is_published = false;
            $course->sort_order = $this->order->next(Course::query());

            if ($thumbnail !== null) {
                $course->thumbnail_path = $this->storeThumbnail($thumbnail);
            }

            $course->save();

            return $course;
        });
    }

    /**
     * @param  array<string, mixed>  $data  Validated course fields.
     */
    public function update(Course $course, array $data, ?UploadedFile $thumbnail, bool $removeThumbnail): Course
    {
        return DB::transaction(function () use ($course, $data, $thumbnail, $removeThumbnail): Course {
            $course->fill(Arr::only($data, [
                'category_id', 'title', 'description', 'cefr_level', 'difficulty',
            ]));

            $course->slug = $data['slug'] ?? $course->slug;

            $oldThumbnail = $course->thumbnail_path;

            if ($thumbnail !== null) {
                $course->thumbnail_path = $this->storeThumbnail($thumbnail);
            } elseif ($removeThumbnail) {
                $course->thumbnail_path = null;
            }

            $course->save();

            if ($oldThumbnail !== null && $oldThumbnail !== $course->thumbnail_path) {
                Storage::disk(config('learning.thumbnail.disk'))->delete($oldThumbnail);
            }

            return $course;
        });
    }

    private function storeThumbnail(UploadedFile $file): string
    {
        $path = $file->store('thumbnails', ['disk' => config('learning.thumbnail.disk')]);

        if ($path === false) {
            throw new \RuntimeException('The thumbnail could not be stored.');
        }

        return $path;
    }
}
