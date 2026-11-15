<?php

namespace App\Modules\Admin\Support;

use App\Modules\Learning\Models\AppSetting;
use Illuminate\Validation\Rule;

/**
 * Describes every setting an admin may change: its group, label, type and limits.
 * The form, the validation and the stored value are all built from this list.
 *
 * Request keys use "__" instead of "." because dots mean nesting in validation.
 */
class SettingsSchema
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public static function fields(): array
    {
        return [
            ['key' => 'dictation.correct_threshold', 'group' => 'Scoring', 'label' => 'Text match needed to count as correct (%)', 'help' => '100 means the text must match exactly after the rules below.', 'type' => 'int', 'min' => 50, 'max' => 100],
            ['key' => 'dictation.ignore_case', 'group' => 'Scoring', 'label' => 'Ignore capital letters', 'type' => 'bool'],
            ['key' => 'dictation.ignore_punctuation', 'group' => 'Scoring', 'label' => 'Ignore punctuation', 'help' => 'Apostrophes and hyphens inside words always count.', 'type' => 'bool'],

            ['key' => 'practice.hints.easy', 'group' => 'Practice', 'label' => 'Hints per item, Easy mode', 'type' => 'int', 'min' => 0, 'max' => 10],
            ['key' => 'practice.hints.medium', 'group' => 'Practice', 'label' => 'Hints per item, Medium mode', 'type' => 'int', 'min' => 0, 'max' => 10],
            ['key' => 'practice.hints.hard', 'group' => 'Practice', 'label' => 'Hints per item, Hard mode', 'type' => 'int', 'min' => 0, 'max' => 10],
            ['key' => 'practice.playback_speeds', 'group' => 'Practice', 'label' => 'Audio speeds', 'help' => 'Numbers separated by commas, for example 0.5, 0.75, 1, 1.25.', 'type' => 'list_float', 'min' => 0.25, 'max' => 3],

            ['key' => 'review.intervals_days', 'group' => 'Review', 'label' => 'Days between reviews', 'help' => 'One number per step, for example 1, 3, 7, 14. A failed review goes back to the first step.', 'type' => 'list_int', 'min' => 1, 'max' => 365],
            ['key' => 'review.batch_size', 'group' => 'Review', 'label' => 'Sentences per review session', 'type' => 'int', 'min' => 1, 'max' => 50],

            ['key' => 'xp.per_correct_sentence', 'group' => 'XP', 'label' => 'XP for a correct sentence', 'type' => 'int', 'min' => 0, 'max' => 1000],
            ['key' => 'xp.per_incorrect_attempt', 'group' => 'XP', 'label' => 'XP for trying (wrong answer)', 'type' => 'int', 'min' => 0, 'max' => 1000],
            ['key' => 'xp.lesson_completion_bonus', 'group' => 'XP', 'label' => 'Bonus for finishing a lesson the first time', 'type' => 'int', 'min' => 0, 'max' => 5000],
            ['key' => 'xp.daily_checkin', 'group' => 'XP', 'label' => 'XP for the daily check-in', 'type' => 'int', 'min' => 0, 'max' => 1000],
            ['key' => 'xp.streak_bonus_per_day', 'group' => 'XP', 'label' => 'Check-in bonus per streak day', 'type' => 'int', 'min' => 0, 'max' => 100],
            ['key' => 'xp.streak_bonus_max', 'group' => 'XP', 'label' => 'Largest check-in streak bonus', 'type' => 'int', 'min' => 0, 'max' => 1000],
            ['key' => 'xp.mode_multiplier.easy', 'group' => 'XP', 'label' => 'XP multiplier, Easy', 'type' => 'float', 'min' => 0.1, 'max' => 10],
            ['key' => 'xp.mode_multiplier.medium', 'group' => 'XP', 'label' => 'XP multiplier, Medium', 'type' => 'float', 'min' => 0.1, 'max' => 10],
            ['key' => 'xp.mode_multiplier.hard', 'group' => 'XP', 'label' => 'XP multiplier, Hard', 'type' => 'float', 'min' => 0.1, 'max' => 10],

            ['key' => 'leaderboard.metric', 'group' => 'Leaderboard', 'label' => 'Rank learners by', 'type' => 'select', 'options' => ['xp' => 'XP earned', 'study_time' => 'Time studied']],
            ['key' => 'leaderboard.size', 'group' => 'Leaderboard', 'label' => 'Learners shown', 'type' => 'int', 'min' => 5, 'max' => 100],
        ];
    }

    public static function id(string $key): string
    {
        return str_replace('.', '__', $key);
    }

    /**
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        $rules = ['values' => ['required', 'array']];

        foreach (self::fields() as $field) {
            $rules['values.'.self::id($field['key'])] = match ($field['type']) {
                'bool' => ['required', 'boolean'],
                'int' => ['required', 'integer', "between:{$field['min']},{$field['max']}"],
                'float' => ['required', 'numeric', "between:{$field['min']},{$field['max']}"],
                'select' => ['required', Rule::in(array_keys($field['options']))],
                default => ['required', 'string', self::listRule($field)],
            };
        }

        return $rules;
    }

    /**
     * Turns the validated form values into typed settings (key => value).
     *
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    public static function parse(array $values): array
    {
        $parsed = [];

        foreach (self::fields() as $field) {
            $raw = $values[self::id($field['key'])];

            $parsed[$field['key']] = match ($field['type']) {
                'bool' => (bool) $raw,
                'int' => (int) $raw,
                'float' => (float) $raw,
                'select' => (string) $raw,
                'list_int' => self::list((string) $raw, true),
                'list_float' => self::list((string) $raw, false),
                default => $raw,
            };
        }

        return $parsed;
    }

    /**
     * What the form shows: lists as "1, 3, 7".
     *
     * @return array<int, array<string, mixed>>
     */
    public static function forForm(): array
    {
        return array_map(function (array $field): array {
            $default = (array) config('learning.defaults', []);
            $value = AppSetting::get($field['key']);
            $fallback = $default[$field['key']] ?? null;

            return $field + [
                'id' => self::id($field['key']),
                'value' => is_array($value) ? implode(', ', $value) : $value,
                'default' => is_array($fallback) ? implode(', ', $fallback) : $fallback,
            ];
        }, self::fields());
    }

    /**
     * @param  array<string, mixed>  $field
     */
    private static function listRule(array $field): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail) use ($field): void {
            $items = preg_split('/[\s,]+/', trim((string) $value), -1, PREG_SPLIT_NO_EMPTY) ?: [];
            $integers = $field['type'] === 'list_int';

            if ($items === [] || count($items) > 12) {
                $fail(__('Enter between 1 and 12 numbers, separated by commas.'));

                return;
            }

            foreach ($items as $item) {
                $valid = $integers ? ctype_digit($item) : is_numeric($item);

                if (! $valid || (float) $item < $field['min'] || (float) $item > $field['max']) {
                    $fail(__('Every number must be between :min and :max.', ['min' => $field['min'], 'max' => $field['max']]));

                    return;
                }
            }
        };
    }

    /**
     * @return array<int, int|float>
     */
    private static function list(string $raw, bool $integers): array
    {
        $items = array_map(
            fn (string $item) => $integers ? (int) $item : (float) $item,
            preg_split('/[\s,]+/', trim($raw), -1, PREG_SPLIT_NO_EMPTY) ?: []
        );

        $items = array_values(array_unique($items));
        sort($items);

        return $items;
    }
}
