<?php

namespace App\Http\Middleware;

use App\Modules\Learning\Models\UserProfile;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The interface language: a signed-in learner's saved choice, otherwise the
 * `locale` cookie, otherwise English. Only languages in `learning.locales`.
 */
class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $profile = $request->user()?->profile;
        $choice = ($profile instanceof UserProfile ? $profile->preferenceList()['language'] ?? null : null)
            ?? $request->cookie('locale');

        app()->setLocale(in_array($choice, config('learning.locales'), true) ? $choice : config('app.locale'));

        return $next($request);
    }
}
