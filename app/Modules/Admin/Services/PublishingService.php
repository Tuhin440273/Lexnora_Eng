<?php

namespace App\Modules\Admin\Services;

use App\Modules\Admin\Exceptions\CannotPublish;
use App\Modules\Courses\Models\Chapter;
use App\Modules\Courses\Models\Course;
use App\Modules\Courses\Models\Lesson;

class PublishingService
{
    /**
     * Flips the published flag and returns the new state.
     *
     * @throws CannotPublish when the content is not ready to be published.
     */
    public function toggle(Course|Chapter|Lesson $content): bool
    {
        $publish = ! $content->is_published;

        if ($publish) {
            $this->assertReady($content);
        }

        $content->is_published = $publish;

        if ($content instanceof Course && $publish && $content->published_at === null) {
            $content->published_at = now();
        }

        $content->save();

        return $publish;
    }

    /**
     * @throws CannotPublish
     */
    private function assertReady(Course|Chapter|Lesson $content): void
    {
        if ($content instanceof Lesson && ! $content->sentences()->exists()) {
            throw new CannotPublish(__('Add at least one sentence before publishing this lesson.'));
        }
    }
}
