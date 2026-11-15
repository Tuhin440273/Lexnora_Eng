<?php

use App\Modules\Courses\Models\Chapter;
use App\Modules\Courses\Models\Course;
use App\Modules\Courses\Models\Lesson;
use App\Modules\Courses\Models\Sentence;
use App\Modules\Courses\Models\SentenceAudio;
use App\Modules\Learning\Enums\ContentType;
use App\Modules\Vocabulary\Models\Vocabulary;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('builds the course > chapter > lesson > sentence hierarchy', function () {
    $course = Course::factory()->published()->create();
    $chapter = Chapter::factory()->for($course)->published()->create();
    $lesson = Lesson::factory()->for($chapter)->published()->create(['estimated_minutes' => 5]);
    Lesson::factory()->for($chapter)->create(['estimated_minutes' => 7]);
    Sentence::factory()->for($lesson)->count(3)->create();

    expect($course->chapters)->toHaveCount(1)
        ->and($course->lessons)->toHaveCount(2)
        ->and($lesson->sentences)->toHaveCount(3)
        ->and($course->estimated_duration_minutes)->toBe(12);
});

it('keeps word and phrase chunks under their parent sentence', function () {
    $lesson = Lesson::factory()->create();
    $sentence = Sentence::factory()->for($lesson)->create();
    Sentence::factory()->word()->for($lesson)->create(['parent_id' => $sentence->id, 'sort_order' => 2]);
    Sentence::factory()->phrase()->for($lesson)->create(['parent_id' => $sentence->id, 'sort_order' => 1]);

    expect($sentence->children)->toHaveCount(2)
        ->and($sentence->children->first()->content_type)->toBe(ContentType::Phrase)
        ->and($lesson->sentences)->toHaveCount(1)        // top level only
        ->and($lesson->allSentences)->toHaveCount(3);    // everything
});

it('returns the primary audio file of a sentence', function () {
    $sentence = Sentence::factory()->create();

    SentenceAudio::create(['sentence_id' => $sentence->id, 'path' => 'audio/other.mp3', 'is_primary' => false]);
    SentenceAudio::create(['sentence_id' => $sentence->id, 'path' => 'audio/main.mp3', 'is_primary' => true]);

    expect($sentence->audio)->toHaveCount(2)
        ->and($sentence->primaryAudio->path)->toBe('audio/main.mp3');
});

it('shows a lesson to learners only when lesson, chapter and course are published', function () {
    $published = Course::factory()->published();
    $draft = Course::factory();

    $visible = Lesson::factory()->published()
        ->for(Chapter::factory()->published()->for($published))->create();
    Lesson::factory()->published()
        ->for(Chapter::factory()->published()->for($draft))->create();          // course is a draft
    Lesson::factory()->published()
        ->for(Chapter::factory()->for($published))->create();                   // chapter is a draft
    Lesson::factory()
        ->for(Chapter::factory()->published()->for($published))->create();      // lesson is a draft

    expect(Lesson::learnerVisible()->pluck('id')->all())->toBe([$visible->id]);
});

it('normalizes vocabulary terms and links them to sentences', function () {
    $sentence = Sentence::factory()->create();
    $vocabulary = Vocabulary::factory()->create(['term' => '  Check-IN   Counter ', 'type' => 'phrase']);

    $sentence->vocabulary()->attach($vocabulary->id, ['position' => 1]);

    expect($vocabulary->normalized_term)->toBe('check-in counter')
        ->and($sentence->vocabulary)->toHaveCount(1)
        ->and($vocabulary->sentences)->toHaveCount(1);
});

it('soft deletes courses instead of removing them', function () {
    $course = Course::factory()->create();

    $course->delete();

    expect(Course::count())->toBe(0)
        ->and(Course::withTrashed()->count())->toBe(1);
});

it('removes children when a course is deleted permanently', function () {
    $chapter = Chapter::factory()->create();
    $lesson = Lesson::factory()->for($chapter)->create();
    Sentence::factory()->for($lesson)->count(2)->create();

    $chapter->course->forceDelete();

    expect(Chapter::withTrashed()->count())->toBe(0)
        ->and(Lesson::withTrashed()->count())->toBe(0)
        ->and(Sentence::withTrashed()->count())->toBe(0);
});
