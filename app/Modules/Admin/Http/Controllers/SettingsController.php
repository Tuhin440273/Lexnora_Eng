<?php

namespace App\Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Admin\Http\Requests\SettingsRequest;
use App\Modules\Admin\Support\SettingsSchema;
use App\Modules\Learning\Models\AppSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    public function edit(): Response
    {
        return Inertia::render('admin/settings/index', ['fields' => SettingsSchema::forForm()]);
    }

    public function update(SettingsRequest $request): RedirectResponse
    {
        $parsed = SettingsSchema::parse($request->validated('values'));
        $groups = collect(SettingsSchema::fields())->pluck('group', 'key');

        DB::transaction(function () use ($parsed, $groups): void {
            foreach ($parsed as $key => $value) {
                AppSetting::set($key, $value, strtolower($groups[$key]));
            }
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Settings saved.')]);

        return back();
    }

    /**
     * Back to the built-in defaults (config/learning.php).
     */
    public function reset(): RedirectResponse
    {
        abort_unless(request()->user()->can('settings.manage'), 403);

        AppSetting::query()->delete();
        Cache::forget(AppSetting::CACHE_KEY);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Settings are back to their defaults.')]);

        return back();
    }
}
