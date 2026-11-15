<?php

use App\Modules\Courses\Models\Sentence;

require_once __DIR__.'/Helpers.php';

beforeEach(function () {
    seedRoles();
    $this->withoutVite();
});

function sentenceData(array $overrides = []): array
{
    return array_merge([
        'content_type' => 'sentence',
        'english_text' => 'I would like a cup of coffee, please.',
        'bengali_text' => 'আমি এক কাপ কফি চাই।',
    ], $overrides);
}

it('adds sentences to the end of a lesson', function () {
    $lesson = lessonTree();
    $this->actingAs(userWithRole('content-manager'));

    $this->post(route('admin.sentences.store', $lesson), sentenceData(['english_text' => 'First.']))
        ->assertSessionHasNoErrors();
    $this->post(route('admin.sentences.store', $lesson), sentenceData(['english_text' => 'Second.']));

    $sentences = $lesson->sentences()->get();

    expect($sentences->pluck('english_text')->all())->toBe(['First.', 'Second.'])
        ->and($sentences->pluck('sort_order')->all())->toBe([1, 2])
        ->and($sentences->first()->bengali_text)->toBe('আমি এক কাপ কফি চাই।');
});

it('requires the english text and a valid type', function () {
    $lesson = lessonTree();
    $this->actingAs(userWithRole('admin'));

    $this->post(route('admin.sentences.store', $lesson), sentenceData(['english_text' => '']))
        ->assertSessionHasErrors('english_text');
    $this->post(route('admin.sentences.store', $lesson), sentenceData(['content_type' => 'paragraph']))
        ->assertSessionHasErrors('content_type');
});

it('adds a word or phrase to a sentence', function () {
    $lesson = lessonTree();
    $parent = Sentence::factory()->for($lesson)->create();
    $this->actingAs(userWithRole('admin'));

    $this->post(route('admin.sentences.store', $lesson), sentenceData([
        'content_type' => 'word', 'english_text' => 'coffee', 'parent_id' => $parent->id,
    ]))->assertSessionHasNoErrors();

    $child = $parent->children()->firstOrFail();

    expect($child->english_text)->toBe('coffee')
        ->and($child->lesson_id)->toBe($lesson->id)
        ->and($child->sort_order)->toBe(1);
});

it('does not allow a chunk to be a full sentence', function () {
    $lesson = lessonTree();
    $parent = Sentence::factory()->for($lesson)->create();

    $this->actingAs(userWithRole('admin'))
        ->post(route('admin.sentences.store', $lesson), sentenceData(['parent_id' => $parent->id]))
        ->assertSessionHasErrors('content_type');

    expect(Sentence::count())->toBe(1);
});

it('does not allow a parent from another lesson or a chunk as a parent', function () {
    $lesson = lessonTree();
    $foreign = Sentence::factory()->for(lessonTree())->create();
    $parent = Sentence::factory()->for($lesson)->create();
    $chunk = Sentence::factory()->word()->for($lesson)->create(['parent_id' => $parent->id]);
    $this->actingAs(userWithRole('admin'));

    $this->post(route('admin.sentences.store', $lesson), sentenceData(['content_type' => 'word', 'parent_id' => $foreign->id]))
        ->assertSessionHasErrors('parent_id');
    $this->post(route('admin.sentences.store', $lesson), sentenceData(['content_type' => 'word', 'parent_id' => $chunk->id]))
        ->assertSessionHasErrors('parent_id');
});

it('updates a sentence but never moves it to another parent or lesson', function () {
    $lesson = lessonTree();
    $other = lessonTree();
    $sentence = Sentence::factory()->for($lesson)->create();
    $this->actingAs(userWithRole('admin'));

    $this->patch(route('admin.sentences.update', $sentence), sentenceData([
        'english_text' => 'Changed.', 'lesson_id' => $other->id, 'parent_id' => 'x',
    ]))->assertSessionHasNoErrors();

    $sentence->refresh();

    expect($sentence->english_text)->toBe('Changed.')
        ->and($sentence->lesson_id)->toBe($lesson->id)
        ->and($sentence->parent_id)->toBeNull();
});

it('keeps a chunk a word or phrase when editing', function () {
    $lesson = lessonTree();
    $parent = Sentence::factory()->for($lesson)->create();
    $chunk = Sentence::factory()->word()->for($lesson)->create(['parent_id' => $parent->id]);

    $this->actingAs(userWithRole('admin'))
        ->patch(route('admin.sentences.update', $chunk), sentenceData())
        ->assertSessionHasErrors('content_type');
});

it('deletes a sentence together with its chunks', function () {
    $lesson = lessonTree();
    $parent = Sentence::factory()->for($lesson)->create();
    Sentence::factory()->word()->for($lesson)->create(['parent_id' => $parent->id]);
    $keep = Sentence::factory()->for($lesson)->create();
    $this->actingAs(userWithRole('admin'));

    $this->delete(route('admin.sentences.destroy', $parent));

    expect(Sentence::pluck('id')->all())->toBe([$keep->id])
        ->and(Sentence::withTrashed()->count())->toBe(3);
});

it('moves a sentence among its siblings only', function () {
    $lesson = lessonTree();
    $a = Sentence::factory()->for($lesson)->create(['english_text' => 'A.', 'sort_order' => 1]);
    $b = Sentence::factory()->for($lesson)->create(['english_text' => 'B.', 'sort_order' => 2]);
    $chunk = Sentence::factory()->word()->for($lesson)->create(['parent_id' => $a->id, 'sort_order' => 1]);
    $this->actingAs(userWithRole('admin'));

    $this->patch(route('admin.sentences.move', $b), ['direction' => 'up']);

    expect($lesson->sentences()->pluck('english_text')->all())->toBe(['B.', 'A.'])
        ->and($chunk->fresh()->sort_order)->toBe(1);

    $other = Sentence::factory()->word()->for($lesson)->create(['parent_id' => $a->id, 'sort_order' => 2]);
    $this->patch(route('admin.sentences.move', $other), ['direction' => 'up']);

    expect($a->children()->pluck('id')->all())->toBe([$other->id, $chunk->id]);
});

it('forbids learners from changing sentences', function () {
    $lesson = lessonTree();
    $sentence = Sentence::factory()->for($lesson)->create();
    $this->actingAs(userWithRole('learner'));

    $this->post(route('admin.sentences.store', $lesson), sentenceData())->assertForbidden();
    $this->patch(route('admin.sentences.update', $sentence), sentenceData())->assertForbidden();
    $this->delete(route('admin.sentences.destroy', $sentence))->assertForbidden();
    $this->patch(route('admin.sentences.move', $sentence), ['direction' => 'down'])->assertForbidden();

    expect(Sentence::count())->toBe(1);
});
