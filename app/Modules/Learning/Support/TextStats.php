<?php

namespace App\Modules\Learning\Support;

class TextStats
{
    /** Words separated by spaces (digits count as words, unlike str_word_count). */
    public static function wordCount(string $text): int
    {
        return count(preg_split('/\s+/u', trim($text), -1, PREG_SPLIT_NO_EMPTY) ?: []);
    }
}
