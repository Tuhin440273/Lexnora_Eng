<?php

namespace App\Modules\Learning\Enums;

enum ReviewStatus: string
{
    case Active = 'active';
    case Mastered = 'mastered';
    case Suspended = 'suspended';
}
