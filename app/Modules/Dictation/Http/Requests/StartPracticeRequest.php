<?php

namespace App\Modules\Dictation\Http\Requests;

use App\Modules\Learning\Enums\PracticeMode;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StartPracticeRequest extends FormRequest
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
        return ['mode' => ['required', Rule::enum(PracticeMode::class)]];
    }
}
