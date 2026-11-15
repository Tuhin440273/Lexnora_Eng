<?php

namespace App\Modules\Courses\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Courses\Http\StoredFileResponse;
use App\Modules\Courses\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class CourseThumbnailController extends Controller
{
    /**
     * Thumbnails of published courses are public (landing page); drafts are
     * only visible to content staff. Anything else answers 404.
     */
    public function show(Request $request, Course $course): Response
    {
        abort_if($course->thumbnail_path === null, 404);

        $isStaff = $request->user()?->canAccessAdmin() ?? false;

        abort_unless($course->is_published || $isStaff, 404);

        $disk = (string) config('learning.thumbnail.disk');

        return StoredFileResponse::make(
            $disk,
            $course->thumbnail_path,
            Storage::disk($disk)->mimeType($course->thumbnail_path) ?: null,
        );
    }
}
