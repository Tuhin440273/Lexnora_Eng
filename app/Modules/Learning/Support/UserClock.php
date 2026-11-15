<?php

namespace App\Modules\Learning\Support;

use App\Models\User;
use Carbon\CarbonImmutable;

/**
 * "Today" for a learner. Timestamps are stored in UTC, but a day (streaks,
 * check-ins, daily statistics) follows the timezone from the learner's profile.
 */
class UserClock
{
    public static function timezone(User $user): string
    {
        $timezone = $user->profile?->timezone ?? 'UTC';

        return in_array($timezone, timezone_identifiers_list(), true) ? $timezone : 'UTC';
    }

    public static function today(User $user): CarbonImmutable
    {
        return CarbonImmutable::now(self::timezone($user))->startOfDay();
    }
}
