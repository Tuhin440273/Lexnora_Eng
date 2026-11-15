<?php

namespace App\Modules\Learning\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class NoteRequest extends FormRequest
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
        return [
            'title' => ['nullable', 'string', 'max:150'],
            'body' => ['required', 'string', 'max:5000'],
            'sentence_id' => ['nullable', 'string', 'size:26'],
        ];
    }
}
