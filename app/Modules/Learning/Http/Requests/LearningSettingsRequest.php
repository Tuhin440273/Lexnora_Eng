<?php

namespace App\Modules\Learning\Http\Requests;

use App\Modules\Learning\Enums\CefrLevel;
use App\Modules\Learning\Enums\PracticeMode;
use App\Modules\Learning\Models\AppSetting;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LearningSettingsRequest extends FormRequest
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
        $speeds = array_map('floatval', (array) AppSetting::get('practice.playback_speeds', [1]));

        return [
            'display_name' => ['nullable', 'string', 'max:60'],
            'target_cefr_level' => ['nullable', Rule::enum(CefrLevel::class)],
            'daily_goal_minutes' => ['required', 'integer', 'between:1,240'],
            'daily_goal_sentences' => ['required', 'integer', 'between:1,500'],
            'timezone' => ['required', Rule::in(timezone_identifiers_list())],
            'leaderboard_anonymous' => ['sometimes', 'boolean'],
            'default_mode' => ['nullable', Rule::enum(PracticeMode::class)],
            'autoplay' => ['sometimes', 'boolean'],
            'default_speed' => ['nullable', 'numeric', Rule::in($speeds)],
        ];
    }
}
