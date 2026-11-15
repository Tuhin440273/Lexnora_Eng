<?php

namespace App\Modules\Gamification\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Gamification\Services\LeaderboardService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LeaderboardController extends Controller
{
    public function index(Request $request, LeaderboardService $leaderboard): Response
    {
        $period = (string) $request->query('period', 'weekly');

        return Inertia::render('leaderboard', $leaderboard->board($request->user(), $period));
    }
}
