<?php

namespace App\Modules\Learning\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Switches the interface language, for guests (a cookie) and learners (also saved on the profile). */
class LanguageController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $data = $request->validate(['locale' => ['required', 'string', Rule::in(config('learning.locales'))]]);

        if ($user = $request->user()) {
            $profile = $user->ensureProfile();
            $profile->update(['preferences' => array_merge($profile->preferenceList(), ['language' => $data['locale']])]);
        }

        return back()->withCookie(cookie('locale', $data['locale'], 60 * 24 * 365));
    }
}
