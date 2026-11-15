<?php

namespace App\Modules\Courses\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Courses\Http\StoredFileResponse;
use App\Modules\Courses\Models\Lesson;
use App\Modules\Courses\Models\SentenceAudio;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AudioStreamController extends Controller
{
    /**
     * Learners may only hear audio of lessons that are published; content
     * staff may hear everything.
     */
    public function show(Request $request, SentenceAudio $audio): Response
    {
        $sentence = $audio->sentence;

        abort_if($sentence === null, 404);

        $allowed = $request->user()->canAccessAdmin()
            || Lesson::learnerVisible()->whereKey($sentence->lesson_id)->exists();

        abort_unless($allowed, 403);

        return StoredFileResponse::make($audio->disk, $audio->path, $audio->mime_type);
    }
}
