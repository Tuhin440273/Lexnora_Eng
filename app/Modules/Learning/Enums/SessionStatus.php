<?php

namespace App\Modules\Learning\Enums;

enum SessionStatus: string
{
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Abandoned = 'abandoned';
}
