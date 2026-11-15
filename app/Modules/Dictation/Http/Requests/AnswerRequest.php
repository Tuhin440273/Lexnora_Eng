<?php

namespace App\Modules\Dictation\Http\Requests;

use App\Modules\Learning\Models\AppSetting;
use Illuminate\Foundation\Http\FormRequest;

class AnswerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Only the typed text matters. The other fields are statistics and are
     * clamped by the server; none of them can change the score or XP.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'answer' => ['required', 'string', 'max:1000'],
            'replay_count' => ['nullable', 'integer', 'min:0', 'max:500'],
            'time_spent_ms' => ['nullable', 'integer', 'min:0', 'max:3600000'],
            'playback_speed' => [
                'nullable', 'numeric',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $allowed = array_map('floatval', (array) AppSetting::get('practice.playback_speeds', [1]));

                    if (! in_array((float) $value, $allowed, true)) {
                        $fail(__('That playback speed is not available.'));
                    }
                },
            ],
        ];
    }
}
