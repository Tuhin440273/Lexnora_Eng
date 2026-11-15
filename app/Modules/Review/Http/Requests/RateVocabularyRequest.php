<?php

namespace App\Modules\Review\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RateVocabularyRequest extends FormRequest
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
        return ['result' => ['required', 'in:remembered,forgot']];
    }
}
