<?php

namespace App\Modules\Admin\Http\Requests;

use App\Modules\Courses\Models\Chapter;
use Illuminate\Foundation\Http\FormRequest;

class ChapterRequest extends FormRequest
{
    public function authorize(): bool
    {
        $chapter = $this->route('chapter');

        return $chapter instanceof Chapter
            ? $this->user()->can('update', $chapter)
            : $this->user()->can('create', Chapter::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
