<?php

use App\Modules\Dictation\Services\HintService;
use App\Modules\Dictation\Services\ScoringService;
use App\Modules\Learning\Models\AppSetting;

function score(string $expected, string $typed): array
{
    return app(ScoringService::class)->score($expected, $typed);
}

it('accepts an exact answer', function () {
    $result = score('I would like a cup of coffee, please.', 'I would like a cup of coffee, please.');

    expect($result['accuracy'])->toBe(100.0)
        ->and($result['is_correct'])->toBeTrue()
        ->and($result['counts'])->toBe(['correct' => 8, 'incorrect' => 0, 'missing' => 0, 'extra' => 0]);
});

it('ignores case, spacing and punctuation by default', function (string $typed) {
    expect(score('Hello, how are you?', $typed)['is_correct'])->toBeTrue();
})->with([
    'lowercase' => ['hello how are you'],
    'uppercase' => ['HELLO HOW ARE YOU'],
    'extra spaces' => ['  hello   how  are   you  '],
    'other punctuation' => ['Hello! How are you.'],
    'tabs and newlines' => ["hello\thow\nare you"],
]);

it('treats curly quotes like straight ones', function () {
    expect(score('I don’t know.', "i don't know")['is_correct'])->toBeTrue();
});

it('keeps apostrophes and hyphens inside words', function () {
    $wrong = score("I don't know.", 'I dont know.');

    expect($wrong['is_correct'])->toBeFalse()
        ->and($wrong['counts']['incorrect'])->toBe(1)
        ->and(score('a check-in desk', 'a checkin desk')['is_correct'])->toBeFalse();
});

it('reports a substituted word', function () {
    $result = score('I like tea', 'I like tee');

    expect($result['is_correct'])->toBeFalse()
        ->and($result['accuracy'])->toBe(90.0)
        ->and($result['tokens'][2])->toBe(['type' => 'incorrect', 'expected' => 'tea', 'typed' => 'tee']);
});

it('reports missing and extra words', function () {
    $missing = score('I would like a cup of coffee', 'I would like cup of coffee');
    $extra = score('I like tea', 'I really like tea');

    expect($missing['counts'])->toBe(['correct' => 6, 'incorrect' => 0, 'missing' => 1, 'extra' => 0])
        ->and(collect($missing['tokens'])->firstWhere('type', 'missing')['expected'])->toBe('a')
        ->and($extra['counts'])->toBe(['correct' => 3, 'incorrect' => 0, 'missing' => 0, 'extra' => 1])
        ->and(collect($extra['tokens'])->firstWhere('type', 'extra')['typed'])->toBe('really');
});

it('shows the words with their original punctuation', function () {
    $result = score('Hello, world!', 'hello world');

    expect($result['tokens'][0]['expected'])->toBe('Hello,')
        ->and($result['tokens'][0]['typed'])->toBe('hello');
});

it('scores an empty answer as zero', function () {
    $result = score('Hello there.', '   ');

    expect($result['accuracy'])->toBe(0.0)
        ->and($result['is_correct'])->toBeFalse()
        ->and($result['counts']['missing'])->toBe(2);
});

it('measures distance in characters, not bytes', function () {
    expect(score('café', 'cafe')['distance'])->toBe(1);
});

it('never goes below zero or above one hundred', function () {
    $result = score('a', 'something completely different and much longer');

    expect($result['accuracy'])->toBeGreaterThanOrEqual(0)->toBeLessThanOrEqual(100);
});

it('follows the admin settings', function () {
    AppSetting::set('dictation.ignore_case', false);
    expect(score('Hello', 'hello')['is_correct'])->toBeFalse();

    AppSetting::set('dictation.ignore_case', true);
    AppSetting::set('dictation.ignore_punctuation', false);
    expect(score('Hello.', 'Hello')['accuracy'])->toBeLessThan(100);

    AppSetting::set('dictation.ignore_punctuation', true);
    AppSetting::set('dictation.correct_threshold', 80);
    expect(score('I like tea', 'I like tee')['is_correct'])->toBeTrue();
});

it('copes with very long input', function () {
    $text = str_repeat('word ', 400);

    expect(score($text, $text.'extra')['accuracy'])->toBeGreaterThan(90);
});

it('builds hints in three levels', function () {
    $hints = app(HintService::class);

    expect($hints->hint('a big red house', 1))->toBe('a b__ r__ h____')
        ->and($hints->hint('a big red house', 2))->toBe('a big r__ h____')
        ->and($hints->hint('a big red house', 3))->toBe('a big red h____')
        ->and($hints->hint('Coffee, please.', 1))->toBe('C_____, p_____.');
});
