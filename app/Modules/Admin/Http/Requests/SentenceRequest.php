<?php

namespace App\Modules\Admin\Http\Requests;

use App\Modules\Courses\Models\Lesson;
use App\Modules\Courses\Models\Sentence;
use App\Modules\Learning\Enums\ContentType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Used for both creating (route has {lesson}) and updating (route has
 * {sentence}). The parent of a chunk can only be chosen when creating it.
 */
class SentenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        $sentence = $this->route('sentence');

        return $sentence instanceof Sentence
            ? $this->user()->can('update', $sentence)
            : $this->user()->can('create', Sentence::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = [
            'english_text' => ['required', 'string', 'max:1000'],
            'bengali_text' => ['nullable', 'string', 'max:2000'],
            'explanation' => ['nullable', 'string', 'max:2000'],
            'content_type' => ['required', Rule::enum(ContentType::class)],
        ];

        $lesson = $this->route('lesson');

        if ($lesson instanceof Lesson) {
            $rules['parent_id'] = [
                'nullable',
                Rule::exists('sentences', 'id')
                    ->where('lesson_id', $lesson->id)
                    ->whereNull('parent_id')
                    ->whereNull('deleted_at'),
            ];
        }

        return $rules;
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $sentence = $this->route('sentence');
            $isChunk = $sentence instanceof Sentence
                ? $sentence->parent_id !== null
                : $this->filled('parent_id');

            if ($isChunk && $this->input('content_type') === ContentType::Sentence->value) {
                $validator->errors()->add('content_type', __('A chunk of a sentence must be a word or a phrase.'));
            }
        }];
    }
}
