<?php

namespace App\Modules\Admin\Http\Requests;

use App\Modules\Courses\Models\Lesson;
use Illuminate\Foundation\Http\FormRequest;

class LessonRequest extends FormRequest
{
    public function authorize(): bool
    {
        $lesson = $this->route('lesson');

        return $lesson instanceof Lesson
            ? $this->user()->can('update', $lesson)
            : $this->user()->can('create', Lesson::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:2000'],
            'difficulty' => ['required', 'integer', 'between:1,5'],
            'estimated_minutes' => ['required', 'integer', 'between:1,240'],
        ];
    }
}
