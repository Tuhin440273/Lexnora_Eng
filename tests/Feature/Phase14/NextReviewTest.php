<?php

use Inertia\Testing\AssertableInertia as Assert;

require_once __DIR__.'/../Phase4/Helpers.php';

beforeEach(function () {
    seedRoles();
    $this->withoutVite();
});

function resultProps(object $user, object $session): array
{
    $props = [];

    test()->actingAs($user)->get(route('practice.show', $session))
        ->assertInertia(function (Assert $page) use (&$props) {
            $props = $page->toArray()['props'];
        });

    return $props;
}

it('tells the learner when a sentence comes back for review', function () {
    $user = learner();
    $session = startPractice($user, publishedLesson(2), 'hard');

    typeAnswer($user, $session, 'sentence number 1');

    $review = resultProps($user, $session)['result']['next_review'];

    expect($review['days'])->toBe(1)
        ->and($review['mastered'])->toBeFalse();
});

it('gives a wrong answer the first step again', function () {
    $user = learner();
    $session = startPractice($user, publishedLesson(2), 'hard');

    typeAnswer($user, $session, 'something else entirely');

    expect(resultProps($user, $session)['result']['next_review']['days'])->toBe(1);
});

it('says nothing about review for a word or a phrase', function () {
    [$lesson] = lessonWithChunks();
    $user = learner();
    $session = startPractice($user, $lesson, 'easy');   // the first item is a word

    typeAnswer($user, $session, 'tea');

    expect(resultProps($user, $session)['result']['next_review'])->toBeNull();
});
