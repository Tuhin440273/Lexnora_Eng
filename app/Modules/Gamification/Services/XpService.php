<?php

namespace App\Modules\Gamification\Services;

use App\Models\User;
use App\Modules\Dictation\Models\PracticeAttempt;
use App\Modules\Dictation\Models\PracticeSession;
use App\Modules\Gamification\Models\UserXpTransaction;
use App\Modules\Learning\Models\AppSetting;
use App\Modules\Learning\Support\UserClock;
use Illuminate\Database\UniqueConstraintViolationException;

/**
 * The only place that creates XP. Amounts come from the admin settings and
 * the server's own records, never from the request. Every award has an
 * idempotency key, so the same reward can never be paid twice.
 */
class XpService
{
    public function award(
        User $user,
        int $amount,
        string $action,
        string $idempotencyKey,
        ?string $sourceType = null,
        ?string $sourceId = null,
        ?string $description = null,
    ): int {
        if ($amount <= 0 || UserXpTransaction::where('idempotency_key', $idempotencyKey)->exists()) {
            return 0;
        }

        try {
            UserXpTransaction::create([
                'user_id' => $user->id,
                'amount' => $amount,
                'action' => $action,
                'source_type' => $sourceType,
                'source_id' => $sourceId,
                'idempotency_key' => $idempotencyKey,
                'description' => $description,
                'awarded_at' => now(),
            ]);
        } catch (UniqueConstraintViolationException) {
            return 0;
        }

        return $amount;
    }

    /**
     * A sentence pays XP at most once per day: the correct amount when it was
     * typed correctly, a small "tried" amount otherwise. Repeating the same
     * lesson all day does not farm points.
     */
    public function awardForAttempt(User $user, PracticeAttempt $attempt, PracticeSession $session): int
    {
        $date = UserClock::today($user)->toDateString();
        $multiplier = (float) AppSetting::get('xp.mode_multiplier.'.$session->mode->value, 1.0);

        if ($attempt->is_correct) {
            $amount = (int) round((int) AppSetting::get('xp.per_correct_sentence', 10) * $multiplier);

            return $this->award(
                $user, $amount, 'dictation_correct',
                "correct:{$user->id}:{$attempt->sentence_id}:{$date}",
                'practice_attempt', $attempt->id,
            );
        }

        return $this->award(
            $user, (int) AppSetting::get('xp.per_incorrect_attempt', 2), 'dictation_tried',
            "tried:{$user->id}:{$attempt->sentence_id}:{$date}",
            'practice_attempt', $attempt->id,
        );
    }

    public function total(User $user): int
    {
        return (int) UserXpTransaction::where('user_id', $user->id)->sum('amount');
    }
}
