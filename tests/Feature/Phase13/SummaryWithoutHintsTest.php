<?php

use Inertia\Testing\AssertableInertia as Assert;

require_once __DIR__.'/../Phase4/Helpers.php';

beforeEach(function () {
    seedRoles();
    $this->withoutVite();
});

it('counts the answers that were right without any hint', function () {
    $user = learner();
    $session = startPractice($user, publishedLesson(3), 'easy');   // easy allows hints
    $this->actingAs($user);

    // sentence 1: a hint, then right
    $this->post(route('practice.hint', $session));
    typeAnswer($user, $session, 'sentence number 1');
    // sentence 2: right, no hint
    typeAnswer($user, $session, 'sentence number 2');
    // sentence 3: wrong, no hint
    typeAnswer($user, $session, 'something else');

    $this->get(route('practice.summary', $session))
        ->assertInertia(fn (Assert $page) => $page
            ->where('session.total', 3)
            ->where('session.correct', 2)
            ->where('session.correct_without_hints', 1));
});

it('counts every right answer when no hint was used', function () {
    $user = learner();
    $session = startPractice($user, publishedLesson(2), 'hard');

    typeAnswer($user, $session, 'sentence number 1');
    typeAnswer($user, $session, 'sentence number 2');

    $this->get(route('practice.summary', $session))
        ->assertInertia(fn (Assert $page) => $page
            ->where('session.correct', 2)
            ->where('session.correct_without_hints', 2));
});
