<?php

namespace App\Modules\Learning\Enums;

enum ContentType: string
{
    case Word = 'word';
    case Phrase = 'phrase';
    case Sentence = 'sentence';

    /** Order used by the EASY mode progression: words -> phrases -> sentences. */
    public function stage(): int
    {
        return match ($this) {
            self::Word => 1,
            self::Phrase => 2,
            self::Sentence => 3,
        };
    }
}
