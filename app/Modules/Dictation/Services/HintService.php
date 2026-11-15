<?php

namespace App\Modules\Dictation\Services;

class HintService
{
    /**
     * Level 1 shows the first letter of every word, level 2 also reveals the
     * first half of the words, level 3 reveals everything but the last word.
     */
    public function hint(string $text, int $level): string
    {
        $words = preg_split('/\s+/u', trim($text), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $count = count($words);

        $revealed = match (true) {
            $level <= 1 => 0,
            $level === 2 => (int) ceil($count / 2),
            default => max(0, $count - 1),
        };

        return implode(' ', array_map(
            fn (string $word, int $index): string => $index < $revealed ? $word : $this->mask($word),
            $words,
            array_keys($words),
        ));
    }

    private function mask(string $word): string
    {
        $seenLetter = false;

        return (string) preg_replace_callback('/\p{L}|\p{N}/u', function (array $match) use (&$seenLetter): string {
            if (! $seenLetter) {
                $seenLetter = true;

                return $match[0];
            }

            return '_';
        }, $word);
    }
}
