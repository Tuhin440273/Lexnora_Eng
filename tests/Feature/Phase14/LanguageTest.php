<?php

use Inertia\Testing\AssertableInertia as Assert;

require_once __DIR__.'/../Phase4/Helpers.php';

beforeEach(function () {
    seedRoles();
    $this->withoutVite();
});

it('shows the site in English unless another language was chosen', function () {
    $this->get(route('home'))->assertInertia(fn (Assert $page) => $page->where('locale', 'en'));
});

it('lets a guest switch to Bangla with a cookie', function () {
    $this->post(route('language.update'), ['locale' => 'bn'])
        ->assertRedirect()
        ->assertCookie('locale', 'bn', false);   // not encrypted, so the browser script can read it too

    $this->withUnencryptedCookie('locale', 'bn')->get(route('home'))
        ->assertInertia(fn (Assert $page) => $page->where('locale', 'bn'));
});

it('refuses a language that is not offered', function () {
    $this->post(route('language.update'), ['locale' => 'fr'])->assertSessionHasErrors('locale');
    $this->post(route('language.update'), [])->assertSessionHasErrors('locale');

    $this->withUnencryptedCookie('locale', 'fr')->get(route('home'))
        ->assertInertia(fn (Assert $page) => $page->where('locale', 'en'));
});

it('keeps a learner\'s choice on their profile, on every device', function () {
    $user = learner();

    $this->actingAs($user)->post(route('language.update'), ['locale' => 'bn']);

    expect($user->fresh()->profile->preferences['language'])->toBe('bn');

    // a fresh browser without the cookie still gets Bangla
    $this->actingAs($user->fresh())->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page->where('locale', 'bn'));
});

it('lets the saved choice win over an old cookie', function () {
    $user = learner();
    $this->actingAs($user)->post(route('language.update'), ['locale' => 'en']);

    $this->actingAs($user->fresh())->withUnencryptedCookie('locale', 'bn')->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page->where('locale', 'en'));
});

it('keeps the language when the learning settings are saved', function () {
    $user = learner();
    $this->actingAs($user)->post(route('language.update'), ['locale' => 'bn']);
    $this->actingAs($user->fresh());

    $this->patch(route('learning-settings.update'), [
        'display_name' => 'Tuhin', 'target_cefr_level' => 'B1', 'daily_goal_minutes' => 25, 'daily_goal_sentences' => 40,
        'timezone' => 'Asia/Dhaka', 'default_mode' => 'hard', 'default_speed' => '0.75', 'autoplay' => '1',
    ])->assertRedirect(route('learning-settings.edit'));

    $preferences = $user->fresh()->profile->preferences;

    expect($preferences['language'])->toBe('bn')
        ->and($preferences['default_mode'])->toBe('hard');
});

it('translates the messages the server sends', function () {
    app()->setLocale('bn');

    expect(__('Saved.'))->toBe('সেভ হয়েছে।')
        ->and(__('Checked in. +:xp XP, :days day streak.', ['xp' => 5, 'days' => 3]))->toBe('চেক-ইন হয়েছে। +5 XP, টানা 3 দিন।');

    app()->setLocale('en');

    expect(__('Saved.'))->toBe('Saved.');
});
