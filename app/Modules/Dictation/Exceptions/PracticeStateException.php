<?php

namespace App\Modules\Dictation\Exceptions;

use RuntimeException;

/** The requested practice action is not possible in the session's current state. */
class PracticeStateException extends RuntimeException {}
