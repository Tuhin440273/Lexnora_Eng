<?php

namespace App\Modules\Learning\Enums;

enum PracticeMode: string
{
    case Easy = 'easy';
    case Medium = 'medium';
    case Hard = 'hard';

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
