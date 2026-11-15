<?php

use App\Models\User;
use App\Modules\Courses\Models\Chapter;
use App\Modules\Courses\Models\Course;
use App\Modules\Courses\Models\Lesson;
use App\Modules\Courses\Models\Sentence;
use App\Modules\Courses\Models\SentenceAudio;
use Database\Seeders\RolesAndPermissionsSeeder;

function seedRoles(): void
{
    app(RolesAndPermissionsSeeder::class)->run();
}

function userWithRole(string $role): User
{
    $user = User::factory()->create();
    $user->assignRole($role);

    return $user;
}

/**
 * A course > chapter > lesson chain. Pass true to publish all three levels.
 */
function lessonTree(bool $published = false): Lesson
{
    $course = Course::factory()->create(['is_published' => $published]);
    $chapter = Chapter::factory()->for($course)->create(['is_published' => $published]);

    return Lesson::factory()->for($chapter)->create(['is_published' => $published]);
}

function audioFor(Sentence $sentence, array $attributes = []): SentenceAudio
{
    return SentenceAudio::create(array_merge([
        'sentence_id' => $sentence->id,
        'disk' => 'local',
        'path' => "audio/{$sentence->id}/test.mp3",
        'original_name' => 'test.mp3',
        'mime_type' => 'audio/mpeg',
        'size_bytes' => 1000,
        'is_primary' => true,
    ], $attributes));
}
