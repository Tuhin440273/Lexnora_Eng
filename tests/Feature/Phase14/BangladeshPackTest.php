<?php

use App\Modules\Courses\Models\Course;
use App\Modules\Courses\Models\Lesson;
use App\Modules\Courses\Models\Sentence;
use App\Modules\Learning\Enums\ContentType;
use App\Modules\Vocabulary\Models\Vocabulary;
use Database\Seeders\BangladeshPackSeeder;
use Database\Seeders\ContentPackSeeder;

require_once __DIR__.'/../Phase4/Helpers.php';

beforeEach(function () {
    config()->set('learning.admin.password', null);   // never depend on the ADMIN_PASSWORD in .env
    seedRoles();
    $this->withoutVite();
});

it('adds two published Bangladesh courses with lessons, sentences, chunks and vocabulary', function () {
    $this->seed(BangladeshPackSeeder::class);

    expect(Course::count())->toBe(2)
        ->and(Course::where('is_published', true)->count())->toBe(2)
        ->and(Lesson::count())->toBe(8)
        ->and(Lesson::learnerVisible()->count())->toBe(8)
        ->and(Sentence::whereNull('parent_id')->count())->toBe(48)
        ->and(Sentence::whereNotNull('parent_id')->count())->toBe(23)
        ->and(Vocabulary::count())->toBe(18)
        ->and(Course::pluck('cefr_level')->map->value->sort()->values()->all())->toBe(['A1', 'A2']);
});

it('can be run again and again without duplicating anything', function () {
    $this->seed(BangladeshPackSeeder::class);
    $this->seed(BangladeshPackSeeder::class);

    expect(Course::count())->toBe(2)
        ->and(Sentence::count())->toBe(71)
        ->and(Vocabulary::count())->toBe(18);
});

it('leaves the first content pack alone and comes after it in the course list', function () {
    $this->seed(ContentPackSeeder::class);
    $this->seed(BangladeshPackSeeder::class);

    expect(Course::count())->toBe(7)
        ->and(Sentence::count())->toBe(77 + 71)
        ->and(Vocabulary::count())->toBe(27 + 18)
        ->and(Course::orderBy('sort_order')->pluck('slug')->slice(-2)->values()->all())
        ->toBe(['life-in-bangladesh', 'getting-around-bangladesh']);
});

it('gives every sentence a clean English text and a Bengali translation', function () {
    $this->seed(BangladeshPackSeeder::class);

    foreach (Sentence::whereNull('parent_id')->get() as $sentence) {
        expect($sentence->english_text)->toBe(trim((string) preg_replace('/\s+/', ' ', $sentence->english_text)))
            ->and($sentence->english_text)->toMatch('/[.?!]$/')
            ->and($sentence->bengali_text)->not->toBeEmpty()
            ->and($sentence->bengali_text)->toMatch('/\p{Bengali}/u');
    }
});

it('keeps each word or phrase inside the sentence it belongs to', function () {
    $this->seed(BangladeshPackSeeder::class);

    foreach (Sentence::whereNotNull('parent_id')->with('parent')->get() as $chunk) {
        expect($chunk->content_type)->toBeIn([ContentType::Word, ContentType::Phrase])
            ->and($chunk->parent->parent_id)->toBeNull()
            ->and($chunk->lesson_id)->toBe($chunk->parent->lesson_id)
            ->and($chunk->bengali_text)->toMatch('/\p{Bengali}/u')
            ->and(mb_strtolower($chunk->parent->english_text))->toContain(mb_strtolower($chunk->english_text));
    }
});

it('links each vocabulary entry to the sentences that use it', function () {
    $this->seed(BangladeshPackSeeder::class);

    $rice = Vocabulary::where('term', 'rice')->firstOrFail();
    $season = Vocabulary::where('term', 'season')->firstOrFail();

    expect($rice->sentences()->count())->toBeGreaterThan(0)
        ->and($season->sentences()->count())->toBeGreaterThan(1);
});

it('can be practised: a learner can start a lesson of the pack', function () {
    $this->seed(BangladeshPackSeeder::class);
    $lesson = Lesson::where('title', 'Rice, Fish and Tea')->firstOrFail();
    $user = learner();

    $session = startPractice($user, $lesson, 'easy');

    expect($session->total_items)->toBeGreaterThan(6);
});
