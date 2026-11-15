<?php

namespace App\Modules\Learning\Enums;

enum CefrLevel: string
{
    case A1 = 'A1';
    case A2 = 'A2';
    case B1 = 'B1';
    case B2 = 'B2';
    case C1 = 'C1';
    case C2 = 'C2';

    public function label(): string
    {
        return match ($this) {
            self::A1 => 'A1 — Beginner',
            self::A2 => 'A2 — Elementary',
            self::B1 => 'B1 — Intermediate',
            self::B2 => 'B2 — Upper Intermediate',
            self::C1 => 'C1 — Advanced',
            self::C2 => 'C2 — Proficiency',
        };
    }

    /** 1 (A1) ... 6 (C2) — handy for sorting and comparisons. */
    public function rank(): int
    {
        return match ($this) {
            self::A1 => 1,
            self::A2 => 2,
            self::B1 => 3,
            self::B2 => 4,
            self::C1 => 5,
            self::C2 => 6,
        };
    }
}
