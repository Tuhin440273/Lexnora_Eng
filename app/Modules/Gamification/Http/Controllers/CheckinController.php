<?php

namespace App\Modules\Gamification\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Gamification\Services\CheckinService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CheckinController extends Controller
{
    public function __invoke(Request $request, CheckinService $checkins): RedirectResponse
    {
        $result = $checkins->checkIn($request->user());

        Inertia::flash('toast', $result['created']
            ? ['type' => 'success', 'message' => __('Checked in. +:xp XP, :days day streak.', [
                'xp' => $result['checkin']->xp_awarded, 'days' => $result['checkin']->streak_after,
            ])]
            : ['type' => 'info', 'message' => __('You already checked in today.')]);

        return back();
    }
}
