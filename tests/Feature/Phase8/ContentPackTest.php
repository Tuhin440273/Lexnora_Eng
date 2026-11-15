<?php

use App\Modules\Courses\Models\Course;
use App\Modules\Courses\Models\Lesson;
use App\Modules\Courses\Models\Sentence;
use App\Modules\Dictation\Models\PracticeSession;
use App\Modules\Learning\Enums\ContentType;
use App\Modules\Vocabulary\Models\Vocabulary;
use Database\Seeders\ContentPackSeeder;

require_once __DIR__.'/../Phase4/Helpers.php';

beforeEach(function () {
    config()->set('learning.admin.password', null);   // never depend on the ADMIN_PASSWORD in .env
    seedRoles();
    $this->withoutVite();
});

it('adds five published courses with lessons, sentences, chunks and vocabulary', function () {
    $this->seed(ContentPackSeeder::class);

    expect(Course::count())->toBe(5)
        ->and(Course::where('is_published', true)->count())->toBe(5)
        ->and(Lesson::count())->toBe(10)
        ->and(Lesson::learnerVisible()->count())->toBe(10)
        ->and(Sentence::whereNull('parent_id')->count())->toBe(60)
        ->and(Sentence::whereNotNull('parent_id')->count())->toBe(17)
        ->and(Vocabulary::count())->toBe(27)
        ->and(Course::pluck('cefr_level')->map->value->sort()->values()->all())->toBe(['A1', 'A2', 'A2', 'B1', 'B2']);
});

it('can be run again and again without duplicating anything', function () {
    $this->seed(ContentPackSeeder::class);
    $this->seed(ContentPackSeeder::class);

    expect(Course::count())->toBe(5)
        ->and(Sentence::count())->toBe(77)
        ->and(Vocabulary::count())->toBe(27);
});

it('gives every sentence a clean English text and a Bengali translation', function () {
    $this->seed(ContentPackSeeder::class);

    foreach (Sentence::whereNull('parent_id')->get() as $sentence) {
        expect($sentence->english_text)->toBe(trim((string) preg_replace('/\s+/', ' ', $sentence->english_text)))
            ->and($sentence->english_text)->toMatch('/[.?!]$/')
            ->and($sentence->bengali_text)->not->toBeEmpty()
            ->and($sentence->bengali_text)->toMatch('/\p{Bengali}/u');
    }
});

it('keeps chunks as words or phrases of a sentence in the same lesson', function () {
    $this->seed(ContentPackSeeder::class);

    foreach (Sentence::whereNotNull('parent_id')->with('parent')->get() as $chunk) {
        expect($chunk->content_type)->toBeIn([ContentType::Word, ContentType::Phrase])
            ->and($chunk->parent->parent_id)->toBeNull()
            ->and($chunk->lesson_id)->toBe($chunk->parent->lesson_id)
            ->and($chunk->bengali_text)->not->toBeEmpty();
    }
});

it('links every vocabulary entry to at least one sentence', function () {
    $this->seed(ContentPackSeeder::class);

    expect(Vocabulary::doesntHave('sentences')->pluck('term')->all())->toBe([]);
});

it('also links older words to the new lessons', function () {
    $this->seed();   // roles, settings, badges and the original demo course
    $this->seed(ContentPackSeeder::class);

    $please = Vocabulary::where('normalized_term', 'please')->firstOrFail();

    expect($please->sentences()->where('english_text', 'I would like to return this, please.')->exists())->toBeTrue()
        ->and(Vocabulary::count())->toBe(40);
});

it('works with the demo content in the same database', function () {
    $this->seed();
    $this->seed(ContentPackSeeder::class);

    expect(Course::count())->toBe(7)
        ->and(Lesson::count())->toBe(14);
});

it('can be practised straight away in every mode', function () {
    $this->seed(ContentPackSeeder::class);
    $lesson = Lesson::where('title', 'Waking Up')->firstOrFail();
    $user = learner();

    expect(startPractice($user, $lesson, 'easy')->total_items)->toBe(9)     // 3 chunks + 6 sentences
        ->and(startPractice($user, $lesson, 'medium')->total_items)->toBe(8) // 2 phrases + 6 sentences
        ->and(startPractice($user, $lesson, 'hard')->total_items)->toBe(6)
        ->and(PracticeSession::count())->toBe(3);

    $this->actingAs($user)->get(route('courses.index'))->assertOk()
        ->assertInertia(fn ($page) => $page->has('courses.data', 5));
});
