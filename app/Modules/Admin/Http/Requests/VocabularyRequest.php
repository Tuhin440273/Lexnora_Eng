<?php

namespace App\Modules\Admin\Http\Requests;

use App\Modules\Learning\Enums\CefrLevel;
use App\Modules\Vocabulary\Models\Vocabulary;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VocabularyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('vocabulary.manage');
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['normalized_term' => Vocabulary::normalizeTerm((string) $this->input('term'))]);
    }

    /**
     * The same word may exist once as a word and once as a phrase, never twice
     * as the same kind (capitals and extra spaces do not make a new entry).
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $entry = $this->route('vocabulary');

        return [
            'term' => ['required', 'string', 'max:150'],
            'normalized_term' => [
                Rule::unique('vocabulary', 'normalized_term')
                    ->where('type', $this->input('type'))
                    ->ignore($entry instanceof Vocabulary ? $entry->id : null),
            ],
            'type' => ['required', Rule::in(['word', 'phrase'])],
            'part_of_speech' => ['nullable', 'string', 'max:30'],
            'meaning_en' => ['nullable', 'string', 'max:1000'],
            'meaning_bn' => ['nullable', 'string', 'max:1000'],
            'pronunciation' => ['nullable', 'string', 'max:100'],
            'example' => ['nullable', 'string', 'max:500'],
            'cefr_level' => ['nullable', Rule::enum(CefrLevel::class)],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return ['normalized_term.unique' => __('This word or phrase is already in the list.')];
    }
}
