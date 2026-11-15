<?php

namespace App\Modules\Vocabulary\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SavedVocabularyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = [
            'personal_meaning' => ['nullable', 'string', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];

        if ($this->isMethod('POST')) {
            $rules += [
                'term' => ['required', 'string', 'max:150'],
                'type' => ['nullable', Rule::in(['word', 'phrase'])],
                'context_sentence_id' => ['nullable', 'string', 'size:26'],
            ];
        }

        return $rules;
    }
}
