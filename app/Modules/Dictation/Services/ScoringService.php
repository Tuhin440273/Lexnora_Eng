<?php

namespace App\Modules\Dictation\Services;

use App\Modules\Learning\Models\AppSetting;

/**
 * Compares what a learner typed with the expected text.
 *
 * The accuracy is a text similarity (character level). It says nothing about
 * pronunciation and must never be presented as if it did.
 */
class ScoringService
{
    private const MAX_LENGTH = 1000;

    /**
     * @return array{
     *     accuracy: float,
     *     is_correct: bool,
     *     distance: int,
     *     tokens: array<int, array{type: string, expected?: string, typed?: string}>,
     *     counts: array{correct: int, incorrect: int, missing: int, extra: int},
     *     expected_words: int
     * }
     */
    public function score(string $expected, string $submitted): array
    {
        $ignoreCase = (bool) AppSetting::get('dictation.ignore_case', true);
        $ignorePunctuation = (bool) AppSetting::get('dictation.ignore_punctuation', true);
        $threshold = (float) AppSetting::get('dictation.correct_threshold', 100);

        $expectedNormalized = $this->normalize($expected, $ignoreCase, $ignorePunctuation);
        $submittedNormalized = $this->normalize($submitted, $ignoreCase, $ignorePunctuation);

        $distance = $this->editDistance(
            mb_str_split(mb_substr($expectedNormalized, 0, self::MAX_LENGTH)),
            mb_str_split(mb_substr($submittedNormalized, 0, self::MAX_LENGTH)),
        );
        $longest = max(mb_strlen($expectedNormalized), mb_strlen($submittedNormalized));

        $accuracy = $longest === 0 || $submittedNormalized === ''
            ? 0.0
            : round(max(0, 1 - $distance / min($longest, self::MAX_LENGTH)) * 100, 2);

        [$tokens, $counts] = $this->diffWords($expected, $submitted, $ignoreCase, $ignorePunctuation);

        return [
            'accuracy' => $accuracy,
            'is_correct' => $submittedNormalized !== '' && $accuracy >= $threshold,
            'distance' => $distance,
            'tokens' => $tokens,
            'counts' => $counts,
            'expected_words' => count($this->words($expected, $ignoreCase, $ignorePunctuation)),
        ];
    }

    /**
     * Lowercases (optional), unifies quotes and dashes, drops punctuation
     * (optional, but keeps apostrophes and hyphens inside words) and squeezes
     * spaces.
     */
    public function normalize(string $text, bool $ignoreCase = true, bool $ignorePunctuation = true): string
    {
        $text = str_replace(
            ['’', '‘', '`', '´', '“', '”', '–', '—'],
            ["'", "'", "'", "'", '"', '"', '-', '-'],
            $text
        );

        if ($ignoreCase) {
            $text = mb_strtolower($text);
        }

        if ($ignorePunctuation) {
            $text = (string) preg_replace("/[^\p{L}\p{N}\s'-]/u", ' ', $text);
            // an apostrophe or hyphen that is not between two letters/digits is punctuation too
            $text = (string) preg_replace("/(?<![\p{L}\p{N}])['-]|['-](?![\p{L}\p{N}])/u", ' ', $text);
        }

        return trim((string) preg_replace('/\s+/u', ' ', $text));
    }

    /**
     * @return array<int, string>
     */
    private function words(string $text, bool $ignoreCase, bool $ignorePunctuation): array
    {
        $normalized = $this->normalize($text, $ignoreCase, $ignorePunctuation);

        return $normalized === '' ? [] : explode(' ', $normalized);
    }

    /**
     * Word-level alignment (edit-distance backtrace). Tokens carry the words
     * as the learner sees them, so the original punctuation is shown.
     *
     * @return array{0: array<int, array{type: string, expected?: string, typed?: string}>, 1: array{correct: int, incorrect: int, missing: int, extra: int}}
     */
    private function diffWords(string $expected, string $submitted, bool $ignoreCase, bool $ignorePunctuation): array
    {
        $expectedWords = $this->displayWords($expected, $ignoreCase, $ignorePunctuation);
        $typedWords = $this->displayWords($submitted, $ignoreCase, $ignorePunctuation);

        $rows = count($expectedWords);
        $cols = count($typedWords);

        $cost = array_fill(0, $rows + 1, array_fill(0, $cols + 1, 0));
        for ($i = 0; $i <= $rows; $i++) {
            $cost[$i][0] = $i;
        }
        for ($j = 0; $j <= $cols; $j++) {
            $cost[0][$j] = $j;
        }
        for ($i = 1; $i <= $rows; $i++) {
            for ($j = 1; $j <= $cols; $j++) {
                $same = $expectedWords[$i - 1]['key'] === $typedWords[$j - 1]['key'] ? 0 : 1;
                $cost[$i][$j] = min(
                    $cost[$i - 1][$j - 1] + $same,
                    $cost[$i - 1][$j] + 1,
                    $cost[$i][$j - 1] + 1,
                );
            }
        }

        $tokens = [];
        $i = $rows;
        $j = $cols;

        while ($i > 0 || $j > 0) {
            if ($i > 0 && $j > 0) {
                $same = $expectedWords[$i - 1]['key'] === $typedWords[$j - 1]['key'];

                if ($cost[$i][$j] === $cost[$i - 1][$j - 1] + ($same ? 0 : 1)) {
                    $tokens[] = $same
                        ? ['type' => 'correct', 'expected' => $expectedWords[$i - 1]['text'], 'typed' => $typedWords[$j - 1]['text']]
                        : ['type' => 'incorrect', 'expected' => $expectedWords[$i - 1]['text'], 'typed' => $typedWords[$j - 1]['text']];
                    $i--;
                    $j--;

                    continue;
                }
            }

            if ($i > 0 && $cost[$i][$j] === $cost[$i - 1][$j] + 1) {
                $tokens[] = ['type' => 'missing', 'expected' => $expectedWords[$i - 1]['text']];
                $i--;
            } else {
                $tokens[] = ['type' => 'extra', 'typed' => $typedWords[$j - 1]['text']];
                $j--;
            }
        }

        $tokens = array_reverse($tokens);

        $counts = ['correct' => 0, 'incorrect' => 0, 'missing' => 0, 'extra' => 0];
        foreach ($tokens as $token) {
            $counts[$token['type']]++;
        }

        return [$tokens, $counts];
    }

    /**
     * @return array<int, array{key: string, text: string}>
     */
    private function displayWords(string $text, bool $ignoreCase, bool $ignorePunctuation): array
    {
        $words = [];

        foreach (preg_split('/\s+/u', trim($text), -1, PREG_SPLIT_NO_EMPTY) ?: [] as $word) {
            $key = $this->normalize($word, $ignoreCase, $ignorePunctuation);

            if ($key !== '') {
                $words[] = ['key' => $key, 'text' => $word];
            }
        }

        return $words;
    }

    /**
     * Levenshtein distance that works on whole characters, not bytes.
     *
     * @param  array<int, string>  $a
     * @param  array<int, string>  $b
     */
    private function editDistance(array $a, array $b): int
    {
        $previous = range(0, count($b));

        foreach ($a as $i => $charA) {
            $current = [$i + 1];

            foreach ($b as $j => $charB) {
                $current[$j + 1] = min(
                    $previous[$j + 1] + 1,
                    $current[$j] + 1,
                    $previous[$j] + ($charA === $charB ? 0 : 1),
                );
            }

            $previous = $current;
        }

        return $previous[count($b)];
    }
}
