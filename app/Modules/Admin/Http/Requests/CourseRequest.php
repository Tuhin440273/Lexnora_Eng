<?php

namespace App\Modules\Admin\Http\Requests;

use App\Modules\Courses\Models\Course;
use App\Modules\Learning\Enums\CefrLevel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

class CourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        $course = $this->route('course');

        return $course instanceof Course
            ? $this->user()->can('update', $course)
            : $this->user()->can('create', Course::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $course = $this->route('course');

        return [
            'title' => ['required', 'string', 'max:150'],
            'slug' => [
                'nullable', 'string', 'max:170',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('courses', 'slug')->ignore($course instanceof Course ? $course->id : null),
            ],
            'description' => ['nullable', 'string', 'max:5000'],
            'category_id' => ['nullable', Rule::exists('categories', 'id')->whereNull('deleted_at')],
            'cefr_level' => ['required', Rule::enum(CefrLevel::class)],
            'difficulty' => ['required', 'integer', 'between:1,5'],
            'thumbnail' => [
                'nullable',
                File::types(config('learning.thumbnail.extensions'))->max(config('learning.thumbnail.max_kb')),
            ],
            'remove_thumbnail' => ['sometimes', 'boolean'],
        ];
    }
}
