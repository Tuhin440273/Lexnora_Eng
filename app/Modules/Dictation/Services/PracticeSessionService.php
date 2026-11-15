<?php

namespace App\Modules\Dictation\Services;

use App\Models\User;
use App\Modules\Analytics\Services\DailyStatsService;
use App\Modules\Courses\Models\Lesson;
use App\Modules\Courses\Models\Sentence;
use App\Modules\Dictation\Exceptions\PracticeStateException;
use App\Modules\Dictation\Models\MistakeRecord;
use App\Modules\Dictation\Models\PracticeAttempt;
use App\Modules\Dictation\Models\PracticeSession;
use App\Modules\Gamification\Services\BadgeService;
use App\Modules\Gamification\Services\XpService;
use App\Modules\Learning\Enums\PracticeMode;
use App\Modules\Learning\Enums\SessionStatus;
use App\Modules\Learning\Models\AppSetting;
use App\Modules\Learning\Services\LessonProgressService;
use App\Modules\Review\Models\ReviewSchedule;
use App\Modules\Review\Services\ReviewScheduleService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Runs a practice session. Everything that matters (which sentence is next,
 * whether an answer is correct, XP, progress, mistakes, reviews) is decided
 * here from the server's own data. The client only sends what was typed.
 */
class PracticeSessionService
{
    /** A learner who stops typing for longer than this is not counted as studying. */
    private const MAX_SECONDS_PER_ANSWER = 120;

    public function __construct(
        private readonly PracticePlanner $planner,
        private readonly ScoringService $scoring,
        private readonly ReviewScheduleService $reviews,
        private readonly XpService $xp,
        private readonly BadgeService $badges,
        private readonly DailyStatsService $stats,
        private readonly LessonProgressService $progress,
    ) {}

    /**
     * Starts a lesson run, or resumes the unfinished one for the same mode.
     */
    public function startLesson(User $user, Lesson $lesson, PracticeMode $mode): PracticeSession
    {
        $existing = PracticeSession::where('user_id', $user->id)
            ->where('lesson_id', $lesson->id)
            ->where('mode', $mode->value)
            ->where('kind', PracticeSession::KIND_LESSON)
            ->where('status', SessionStatus::InProgress->value)
            ->latest('id')
            ->first();

        if ($existing) {
            return $existing;
        }

        $plan = $this->planner->plan($lesson, $mode);

        if ($plan === []) {
            throw new PracticeStateException(__('This lesson has no sentences yet.'));
        }

        $session = $this->create($user, $plan, $mode, PracticeSession::KIND_LESSON, $lesson->id);
        $this->progress->started($user, $lesson, $mode);

        return $session;
    }

    /**
     * A session over specific sentences: review of due items or "practice again" of mistakes.
     *
     * @param  array<int, string>  $sentenceIds
     */
    public function startForSentences(User $user, array $sentenceIds, string $kind): PracticeSession
    {
        $found = Sentence::whereIn('id', $sentenceIds)->pluck('id')->all();
        $plan = array_values(array_filter($sentenceIds, fn (string $id) => in_array($id, $found, true)));

        if ($plan === []) {
            throw new PracticeStateException(__('There is nothing to practise right now.'));
        }

        return $this->create($user, $plan, PracticeMode::Hard, $kind, null);
    }

    /**
     * The sentence the learner must type next. Sentences that were deleted
     * after the session started are skipped.
     */
    public function currentSentence(PracticeSession $session): ?Sentence
    {
        while ($session->completed_items < $session->total_items) {
            $id = $session->plan[$session->completed_items] ?? null;
            $sentence = $id ? Sentence::with('primaryAudio')->find($id) : null;

            if ($sentence) {
                return $sentence;
            }

            $session->increment('completed_items');
        }

        return null;
    }

    public function hintKey(PracticeSession $session, Sentence $sentence): string
    {
        return "practice:{$session->id}:{$sentence->id}:hints";
    }

    /**
     * Scores the answer for the current sentence and updates everything that depends on it.
     *
     * @param  array{answer: string, replay_count?: int|null, playback_speed?: float|null, time_spent_ms?: int|null}  $input
     * @return array<string, mixed>
     *
     * @throws PracticeStateException
     */
    public function submit(PracticeSession $session, User $user, array $input): array
    {
        return DB::transaction(function () use ($session, $user, $input): array {
            $session = PracticeSession::whereKey($session->id)->lockForUpdate()->firstOrFail();

            if ($session->isFinished()) {
                throw new PracticeStateException(__('This practice session is already finished.'));
            }

            $sentence = $this->currentSentence($session);

            if ($sentence === null) {
                throw new PracticeStateException(__('There is nothing left to answer in this session.'));
            }

            $answer = trim($input['answer']);
            $scored = $this->scoring->score($sentence->english_text, $answer);

            $attempt = PracticeAttempt::create([
                'practice_session_id' => $session->id,
                'user_id' => $user->id,
                'sentence_id' => $sentence->id,
                'attempt_number' => PracticeAttempt::where('practice_session_id', $session->id)
                    ->where('sentence_id', $sentence->id)->count() + 1,
                'submitted_text' => $answer,
                'expected_text' => $sentence->english_text,
                'accuracy' => $scored['accuracy'],
                'is_correct' => $scored['is_correct'],
                'error_details' => ['tokens' => $scored['tokens'], 'counts' => $scored['counts'], 'distance' => $scored['distance']],
                'hints_used' => (int) Cache::pull($this->hintKey($session, $sentence), 0),
                'replay_count' => max(0, min(500, (int) ($input['replay_count'] ?? 0))),
                'playback_speed' => $input['playback_speed'] ?? null,
                'time_spent_ms' => isset($input['time_spent_ms']) ? max(0, min(3_600_000, (int) $input['time_spent_ms'])) : null,
                'answered_at' => now(),
            ]);

            $since = $session->last_activity_at ?? $session->started_at;
            $seconds = max(0, min(self::MAX_SECONDS_PER_ANSWER, (int) $since->diffInSeconds(now())));

            $session->update([
                'completed_items' => $session->completed_items + 1,
                'correct_items' => $session->correct_items + ($scored['is_correct'] ? 1 : 0),
                'duration_seconds' => $session->duration_seconds + $seconds,
                'last_activity_at' => now(),
            ]);

            $this->trackMistake($user, $sentence, $attempt);
            $this->trackReview($user, $session, $sentence, $attempt);

            $xp = $this->xp->awardForAttempt($user, $attempt, $session);
            $session->increment('xp_awarded', $xp);

            $this->stats->add($user, [
                'attempts_count' => 1,
                'correct_attempts' => $scored['is_correct'] ? 1 : 0,
                'accuracy_total' => $scored['accuracy'],
                'study_seconds' => $seconds,
                'xp_earned' => $xp,
            ]);

            $finish = $session->completed_items >= $session->total_items
                ? $this->complete($session, $user)
                : null;

            return [
                'attempt_id' => $attempt->id,
                'sentence_id' => $sentence->id,
                'content_type' => $sentence->content_type->value,
                'is_correct' => $scored['is_correct'],
                'accuracy' => $scored['accuracy'],
                'tokens' => $scored['tokens'],
                'counts' => $scored['counts'],
                'typed' => $answer,
                'expected' => $sentence->english_text,
                'bengali' => $sentence->bengali_text,
                'explanation' => $sentence->explanation,
                'xp' => $xp + ($finish['lesson_bonus'] ?? 0),
                'finished' => $finish !== null,
                'badges' => $finish['badges'] ?? [],
            ];
        });
    }

    /**
     * Closes a session whose remaining sentences no longer exist.
     */
    public function closeIfExhausted(PracticeSession $session, User $user): void
    {
        if ($session->isFinished() || $this->currentSentence($session) !== null) {
            return;
        }

        if ($session->attempts()->exists()) {
            $this->complete($session, $user);
        } else {
            $session->update(['status' => SessionStatus::Abandoned]);
        }
    }

    /**
     * @return array{lesson_bonus: int, badges: array<int, array<string, mixed>>}
     */
    private function complete(PracticeSession $session, User $user): array
    {
        $average = round((float) $session->attempts()->avg('accuracy'), 2);

        $session->update([
            'status' => SessionStatus::Completed,
            'completed_at' => now(),
            'average_accuracy' => $average,
        ]);

        $this->stats->add($user, ['sessions_count' => 1]);

        $bonus = 0;

        if ($session->kind === PracticeSession::KIND_LESSON && $session->lesson) {
            $firstCompletion = $this->progress->finished($user, $session->lesson, $session->mode, $average);

            if ($firstCompletion) {
                $bonus = $this->xp->award(
                    $user,
                    (int) AppSetting::get('xp.lesson_completion_bonus', 25),
                    'lesson_completed',
                    "lesson:{$user->id}:{$session->lesson_id}",
                    'lesson',
                    $session->lesson_id,
                );
                $session->increment('xp_awarded', $bonus);
                $this->stats->add($user, ['lessons_completed' => 1, 'xp_earned' => $bonus]);
            }
        }

        $earned = $this->badges->check($user)->map(fn ($badge) => [
            'name' => $badge->name,
            'description' => $badge->description,
            'icon' => $badge->icon,
            'xp_reward' => $badge->xp_reward,
        ])->all();

        return ['lesson_bonus' => $bonus, 'badges' => $earned];
    }

    private function trackMistake(User $user, Sentence $sentence, PracticeAttempt $attempt): void
    {
        $record = MistakeRecord::where('user_id', $user->id)->where('sentence_id', $sentence->id)->first();

        if (! $attempt->is_correct) {
            $record
                ? $record->update([
                    'times_missed' => $record->times_missed + 1,
                    'latest_attempt_id' => $attempt->id,
                    'last_missed_at' => now(),
                    'resolved_at' => null,
                ])
                : MistakeRecord::create([
                    'user_id' => $user->id,
                    'sentence_id' => $sentence->id,
                    'latest_attempt_id' => $attempt->id,
                    'times_missed' => 1,
                    'last_missed_at' => now(),
                ]);

            return;
        }

        if ($record && $record->resolved_at === null) {
            $record->update(['resolved_at' => now()]);
        }
    }

    private function trackReview(User $user, PracticeSession $session, Sentence $sentence, PracticeAttempt $attempt): void
    {
        if ($sentence->parent_id !== null) {
            return; // only full sentences are scheduled, not their words and phrases
        }

        if ($session->kind === PracticeSession::KIND_REVIEW) {
            $schedule = ReviewSchedule::where('user_id', $user->id)
                ->where('reviewable_type', $sentence->getMorphClass())
                ->where('reviewable_id', $sentence->id)
                ->first();

            if ($schedule) {
                $this->reviews->recordResult($schedule, $attempt->is_correct, (float) $attempt->accuracy, $attempt);

                return;
            }
        }

        $this->reviews->ensureScheduled($user, $sentence);
    }

    /**
     * @param  array<int, string>  $plan
     */
    private function create(User $user, array $plan, PracticeMode $mode, string $kind, ?string $lessonId): PracticeSession
    {
        return PracticeSession::create([
            'user_id' => $user->id,
            'lesson_id' => $lessonId,
            'mode' => $mode,
            'kind' => $kind,
            'plan' => $plan,
            'status' => SessionStatus::InProgress,
            'total_items' => count($plan),
            'started_at' => now(),
            'last_activity_at' => now(),
        ]);
    }
}
