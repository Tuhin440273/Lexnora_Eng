<?php

use App\Modules\Admin\Services\VocabularyLinker;
use App\Modules\Courses\Models\Sentence;
use App\Modules\Vocabulary\Models\SavedVocabulary;
use App\Modules\Vocabulary\Models\Vocabulary;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

require_once __DIR__.'/../Phase4/Helpers.php';

beforeEach(function () {
    seedRoles();
    $this->withoutVite();
});

function entryData(array $overrides = []): array
{
    return array_merge([
        'term' => 'coffee', 'type' => 'word', 'part_of_speech' => 'noun',
        'meaning_en' => 'a hot drink', 'meaning_bn' => 'কফি', 'cefr_level' => 'A1', 'is_active' => '1',
    ], $overrides);
}

function sentenceWith(string $text): Sentence
{
    return Sentence::factory()->for(lessonTree())->create(['english_text' => $text]);
}

it('is open to people who may manage vocabulary only', function () {
    $this->get(route('admin.vocabulary.index'))->assertRedirect(route('login'));
    $this->actingAs(learner())->get(route('admin.vocabulary.index'))->assertForbidden();
    $this->actingAs(userWithRole('content-manager'))->get(route('admin.vocabulary.index'))->assertOk();
    $this->actingAs(userWithRole('admin'))->get(route('admin.vocabulary.index'))->assertOk();

    $user = userWithRole('content-manager');
    Role::findByName('content-manager')->revokePermissionTo('vocabulary.manage');
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $this->actingAs($user)->get(route('admin.vocabulary.index'))->assertForbidden();
    $this->post(route('admin.vocabulary.store'), entryData())->assertForbidden();
});

it('lists, searches and filters the entries', function () {
    Vocabulary::factory()->create(['term' => 'tea', 'meaning_bn' => 'চা', 'cefr_level' => 'A1']);
    Vocabulary::factory()->create(['term' => 'a cup of tea', 'type' => 'phrase', 'cefr_level' => 'A2']);
    Vocabulary::factory()->create(['term' => 'economy', 'is_active' => false, 'cefr_level' => 'B2']);
    $this->actingAs(userWithRole('content-manager'));

    $this->get(route('admin.vocabulary.index'))->assertInertia(fn (Assert $page) => $page
        ->component('admin/vocabulary/index')->has('entries.data', 3)->has('cefr_levels', 6));
    $this->get(route('admin.vocabulary.index', ['search' => 'চা']))->assertInertia(fn (Assert $page) => $page->has('entries.data', 1));
    $this->get(route('admin.vocabulary.index', ['type' => 'phrase']))->assertInertia(fn (Assert $page) => $page->has('entries.data', 1)->where('entries.data.0.term', 'a cup of tea'));
    $this->get(route('admin.vocabulary.index', ['cefr_level' => 'B2']))->assertInertia(fn (Assert $page) => $page->has('entries.data', 1));
    $this->get(route('admin.vocabulary.index', ['status' => 'hidden']))->assertInertia(fn (Assert $page) => $page->has('entries.data', 1)->where('entries.data.0.term', 'economy'));
    $this->get(route('admin.vocabulary.index', ['type' => 'verb']))->assertSessionHasErrors('type');
});

it('adds an entry and links it to the sentences that use it', function () {
    $uses = sentenceWith('I would like a cup of coffee, please.');
    $plural = sentenceWith('Two coffees, please.');
    $other = sentenceWith('Tea is nice.');
    $user = userWithRole('content-manager');

    $this->actingAs($user)->post(route('admin.vocabulary.store'), entryData(['term' => '  Coffee ']))->assertSessionHasNoErrors();

    $entry = Vocabulary::firstOrFail();

    expect($entry->term)->toBe('Coffee')
        ->and($entry->normalized_term)->toBe('coffee')
        ->and($entry->created_by)->toBe($user->id)
        ->and($entry->cefr_level->value)->toBe('A1')
        ->and($entry->sentences->pluck('id')->sort()->values()->all())->toBe(collect([$uses->id, $plural->id])->sort()->values()->all())
        ->and($other->vocabulary()->count())->toBe(0);
});

it('refuses a duplicate of the same kind, but allows a word and a phrase', function () {
    Vocabulary::factory()->create(['term' => 'Coffee', 'type' => 'word']);
    $this->actingAs(userWithRole('admin'));

    $this->post(route('admin.vocabulary.store'), entryData(['term' => '  coffee  ']))->assertSessionHasErrors('normalized_term');
    $this->post(route('admin.vocabulary.store'), entryData(['term' => 'coffee', 'type' => 'phrase']))->assertSessionHasNoErrors();

    expect(Vocabulary::count())->toBe(2);
});

it('validates the fields', function (array $bad, string $field) {
    $this->actingAs(userWithRole('admin'))->post(route('admin.vocabulary.store'), entryData($bad))->assertSessionHasErrors($field);

    expect(Vocabulary::count())->toBe(0);
})->with([
    'no term' => [['term' => ''], 'term'],
    'bad type' => [['type' => 'sentence'], 'type'],
    'bad level' => [['cefr_level' => 'Z9'], 'cefr_level'],
    'long meaning' => [['meaning_en' => str_repeat('a', 1001)], 'meaning_en'],
    'long example' => [['example' => str_repeat('a', 501)], 'example'],
]);

it('updates an entry, keeps its own name valid and finds new sentences', function () {
    $entry = Vocabulary::factory()->create(['term' => 'tea', 'meaning_bn' => null]);
    $later = sentenceWith('I drink tea every day.');
    $this->actingAs(userWithRole('content-manager'));

    $this->patch(route('admin.vocabulary.update', $entry), entryData(['term' => 'tea', 'meaning_bn' => 'চা', 'is_active' => '1']))->assertSessionHasNoErrors();

    expect($entry->fresh()->meaning_bn)->toBe('চা')->and($later->vocabulary()->count())->toBe(1);

    Vocabulary::factory()->create(['term' => 'milk']);
    $this->patch(route('admin.vocabulary.update', $entry), entryData(['term' => 'Milk']))->assertSessionHasErrors('normalized_term');
});

it('hides an entry without linking it', function () {
    $entry = Vocabulary::factory()->create(['term' => 'tea']);
    $sentence = sentenceWith('Tea is nice.');
    $this->actingAs(userWithRole('admin'));

    $this->patch(route('admin.vocabulary.update', $entry), Arr::except(entryData(['term' => 'tea']), 'is_active'));   // checkbox left out = hidden
    expect($entry->fresh()->is_active)->toBeFalse()->and($sentence->vocabulary()->count())->toBe(0);

    $this->post(route('admin.vocabulary.link', $entry));
    expect($sentence->vocabulary()->count())->toBe(0);
});

it('links an entry to sentences written later on request', function () {
    $entry = Vocabulary::factory()->create(['term' => 'receipt']);
    $sentence = sentenceWith('Here is your receipt.');
    expect($sentence->vocabulary()->count())->toBe(0);

    $this->actingAs(userWithRole('content-manager'))->post(route('admin.vocabulary.link', $entry))->assertRedirect();

    expect($sentence->vocabulary()->count())->toBe(1);
});

it('removes an entry but keeps what learners saved', function () {
    $entry = Vocabulary::factory()->create(['term' => 'tea']);
    $sentence = sentenceWith('Tea is nice.');
    $sentence->vocabulary()->attach($entry->id);
    $saved = SavedVocabulary::create(['user_id' => learner()->id, 'term' => 'tea', 'vocabulary_id' => $entry->id]);

    $this->actingAs(userWithRole('admin'))->delete(route('admin.vocabulary.destroy', $entry));

    expect(Vocabulary::count())->toBe(0)
        ->and(DB::table('sentence_vocabulary')->count())->toBe(0)
        ->and($saved->fresh()->vocabulary_id)->toBeNull()
        ->and($saved->fresh()->term)->toBe('tea');
});

it('matches whole words, plurals and phrases only', function (string $term, string $text, bool $expected) {
    expect(app(VocabularyLinker::class)->matches($text, $term))->toBe($expected);
})->with([
    ['change', 'Keep the change.', true],
    ['change', 'It is an exchange.', false],
    ['card', 'Do you accept credit cards?', true],
    ['allergy', 'Do you have any allergies?', true],
    ['bus', 'My bus leaves at eight.', true],
    ['bus', 'I like the busy street.', false],
    ['tea', 'I will have TEA.', true],
    ['tea', 'Steam rises from the teapot.', false],
    ['check-in counter', 'Where is the check-in counter?', true],
    ['to go', 'I will have it to go.', true],
    ['', 'Anything.', false],
]);

it('links vocabulary when a sentence is added or edited in the lesson editor', function () {
    $tea = Vocabulary::factory()->create(['term' => 'tea']);
    $milk = Vocabulary::factory()->create(['term' => 'milk']);
    $lesson = lessonTree();
    $this->actingAs(userWithRole('content-manager'));

    $this->post(route('admin.sentences.store', $lesson), ['content_type' => 'sentence', 'english_text' => 'I like tea and milk.']);
    $sentence = Sentence::firstOrFail();
    expect($sentence->vocabulary->pluck('id')->sort()->values()->all())->toBe(collect([$tea->id, $milk->id])->sort()->values()->all());

    $this->patch(route('admin.sentences.update', $sentence), ['content_type' => 'sentence', 'english_text' => 'I like only tea.']);
    expect($sentence->fresh()->vocabulary->pluck('id')->all())->toBe([$tea->id]);
});

it('does not link words and phrases that belong to a sentence', function () {
    Vocabulary::factory()->create(['term' => 'tea']);
    $lesson = lessonTree();
    $parent = Sentence::factory()->for($lesson)->create(['english_text' => 'Hello.']);
    $this->actingAs(userWithRole('admin'))->post(route('admin.sentences.store', $lesson), [
        'content_type' => 'word', 'english_text' => 'tea', 'parent_id' => $parent->id,
    ]);

    expect(DB::table('sentence_vocabulary')->count())->toBe(0);
});

it('shows how many sentences use an entry, in a fixed number of queries', function () {
    $count = function (): int {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->get(route('admin.vocabulary.index'))->assertOk();

        return count(DB::getQueryLog());
    };
    $this->actingAs(userWithRole('admin'));

    Vocabulary::factory()->count(2)->create();
    $this->get(route('admin.vocabulary.index'));   // warm-up
    $small = $count();

    Vocabulary::factory()->count(8)->create();
    $large = $count();

    expect($large)->toBeLessThanOrEqual($small);
});
