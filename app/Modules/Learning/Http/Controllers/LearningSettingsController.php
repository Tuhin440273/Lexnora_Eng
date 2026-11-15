<?php

namespace App\Modules\Learning\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Learning\Enums\CefrLevel;
use App\Modules\Learning\Enums\PracticeMode;
use App\Modules\Learning\Http\Requests\LearningSettingsRequest;
use App\Modules\Learning\Models\AppSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LearningSettingsController extends Controller
{
    public function edit(Request $request): Response
    {
        $profile = $request->user()->ensureProfile();

        return Inertia::render('settings/learning', [
            'profile' => [
                'display_name' => $profile->display_name,
                'target_cefr_level' => $profile->target_cefr_level?->value,
                'daily_goal_minutes' => $profile->daily_goal_minutes,
                'daily_goal_sentences' => $profile->daily_goal_sentences,
                'timezone' => $profile->timezone,
                'leaderboard_anonymous' => $profile->leaderboard_anonymous,
                'default_mode' => $profile->preferences['default_mode'] ?? 'medium',
                'default_speed' => (float) ($profile->preferences['default_speed'] ?? 1),
            ],
            'options' => [
                'cefr_levels' => array_map(fn (CefrLevel $l) => ['value' => $l->value, 'label' => $l->label()], CefrLevel::cases()),
                'modes' => array_map(fn (PracticeMode $m) => ['value' => $m->value, 'label' => $m->label()], PracticeMode::cases()),
                'speeds' => array_map('floatval', (array) AppSetting::get('practice.playback_speeds', [1])),
                'timezones' => timezone_identifiers_list(),
            ],
        ]);
    }

    public function update(LearningSettingsRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $request->user()->ensureProfile()->update([
            'display_name' => $data['display_name'] ?? null,
            'target_cefr_level' => $data['target_cefr_level'] ?? null,
            'daily_goal_minutes' => $data['daily_goal_minutes'],
            'daily_goal_sentences' => $data['daily_goal_sentences'],
            'timezone' => $data['timezone'],
            'leaderboard_anonymous' => $request->boolean('leaderboard_anonymous'),
            'preferences' => [
                'default_mode' => $data['default_mode'] ?? 'medium',
                'default_speed' => isset($data['default_speed']) ? (float) $data['default_speed'] : 1.0,
            ],
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Learning settings saved.')]);

        return to_route('learning-settings.edit');
    }
}
