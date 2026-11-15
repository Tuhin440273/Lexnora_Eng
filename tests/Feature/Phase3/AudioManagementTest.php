<?php

use App\Modules\Courses\Models\Sentence;
use App\Modules\Courses\Models\SentenceAudio;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

require_once __DIR__.'/Helpers.php';

beforeEach(function () {
    seedRoles();
    $this->withoutVite();
    Storage::fake('local');
});

function mp3(string $name = 'hello.mp3', int $kb = 50): UploadedFile
{
    return UploadedFile::fake()->create($name, $kb, 'audio/mpeg');
}

it('stores an upload with a generated name and records its details', function () {
    $sentence = Sentence::factory()->for(lessonTree())->create();
    $user = userWithRole('content-manager');

    $this->actingAs($user)
        ->post(route('admin.audio.store', $sentence), [
            'audio' => mp3('My Recording.mp3', 64),
            'duration_ms' => 2400,
            'voice_label' => 'Sara',
        ])
        ->assertSessionHasNoErrors();

    $audio = SentenceAudio::firstOrFail();

    Storage::disk('local')->assertExists($audio->path);

    expect($audio->path)->toStartWith("audio/{$sentence->id}/")
        ->and($audio->path)->toEndWith('.mp3')
        ->and($audio->path)->not->toContain('My Recording')
        ->and($audio->original_name)->toBe('My Recording.mp3')
        ->and($audio->mime_type)->toBe('audio/mpeg')
        ->and($audio->size_bytes)->toBeGreaterThan(0)
        ->and($audio->duration_ms)->toBe(2400)
        ->and($audio->voice_label)->toBe('Sara')
        ->and($audio->uploaded_by)->toBe($user->id)
        ->and($audio->disk)->toBe('local')
        ->and($audio->is_primary)->toBeTrue();
});

it('makes only the first file primary unless asked', function () {
    $sentence = Sentence::factory()->for(lessonTree())->create();
    $this->actingAs(userWithRole('admin'));

    $this->post(route('admin.audio.store', $sentence), ['audio' => mp3('one.mp3')]);
    $this->post(route('admin.audio.store', $sentence), ['audio' => mp3('two.mp3')]);

    $files = SentenceAudio::orderBy('id')->get();
    expect($files->pluck('is_primary')->all())->toBe([true, false]);

    $this->post(route('admin.audio.store', $sentence), ['audio' => mp3('three.mp3'), 'make_primary' => '1']);

    $files = SentenceAudio::orderBy('id')->get();
    expect($files->pluck('is_primary')->all())->toBe([false, false, true])
        ->and($sentence->primaryAudio->original_name)->toBe('three.mp3');
});

it('rejects files that are not audio', function (string $name, string $mime) {
    $sentence = Sentence::factory()->for(lessonTree())->create();

    $this->actingAs(userWithRole('admin'))
        ->post(route('admin.audio.store', $sentence), ['audio' => UploadedFile::fake()->create($name, 10, $mime)])
        ->assertSessionHasErrors('audio');

    expect(SentenceAudio::count())->toBe(0)
        ->and(Storage::disk('local')->allFiles())->toBe([]);
})->with([
    'a text file' => ['notes.txt', 'text/plain'],
    'a script' => ['run.php', 'application/x-php'],
    'an image' => ['photo.png', 'image/png'],
    'a text file renamed to mp3' => ['fake.mp3', 'text/plain'],
    'an audio type with a bad extension' => ['sound.exe', 'audio/mpeg'],
]);

it('rejects a missing, oversized or malformed upload', function () {
    $sentence = Sentence::factory()->for(lessonTree())->create();
    $this->actingAs(userWithRole('admin'));

    $this->post(route('admin.audio.store', $sentence), [])->assertSessionHasErrors('audio');
    $this->post(route('admin.audio.store', $sentence), ['audio' => mp3('big.mp3', 20000)])->assertSessionHasErrors('audio');
    $this->post(route('admin.audio.store', $sentence), ['audio' => mp3(), 'duration_ms' => -5])->assertSessionHasErrors('duration_ms');
    $this->post(route('admin.audio.store', $sentence), ['audio' => mp3(), 'duration_ms' => 'abc'])->assertSessionHasErrors('duration_ms');

    expect(SentenceAudio::count())->toBe(0);
});

it('accepts every allowed audio type', function (string $name, string $mime) {
    $sentence = Sentence::factory()->for(lessonTree())->create();

    $this->actingAs(userWithRole('admin'))
        ->post(route('admin.audio.store', $sentence), ['audio' => UploadedFile::fake()->create($name, 10, $mime)])
        ->assertSessionHasNoErrors();

    expect(SentenceAudio::count())->toBe(1);
})->with([
    ['a.mp3', 'audio/mpeg'],
    ['a.wav', 'audio/x-wav'],
    ['a.ogg', 'audio/ogg'],
    ['a.m4a', 'audio/x-m4a'],
    ['a.m4a', 'audio/mp4'],
    ['a.aac', 'audio/aac'],
    ['a.webm', 'audio/webm'],
]);

it('lets a learner neither upload, change nor delete audio', function () {
    $sentence = Sentence::factory()->for(lessonTree())->create();
    $audio = audioFor($sentence);
    $this->actingAs(userWithRole('learner'));

    $this->post(route('admin.audio.store', $sentence), ['audio' => mp3()])->assertForbidden();
    $this->patch(route('admin.audio.primary', $audio))->assertForbidden();
    $this->delete(route('admin.audio.destroy', $audio))->assertForbidden();

    expect(SentenceAudio::count())->toBe(1);
});

it('needs the audio permission, not just content access', function () {
    $user = userWithRole('content-manager');
    Role::findByName('content-manager')->revokePermissionTo('audio.manage');
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $sentence = Sentence::factory()->for(lessonTree())->create();

    $this->actingAs($user)
        ->post(route('admin.audio.store', $sentence), ['audio' => mp3()])
        ->assertForbidden();
});

it('changes the primary file', function () {
    $sentence = Sentence::factory()->for(lessonTree())->create();
    $first = audioFor($sentence, ['is_primary' => true]);
    $second = audioFor($sentence, ['is_primary' => false, 'path' => 'audio/x/second.mp3']);
    $this->actingAs(userWithRole('admin'));

    $this->patch(route('admin.audio.primary', $second));

    expect($first->fresh()->is_primary)->toBeFalse()
        ->and($second->fresh()->is_primary)->toBeTrue();
});

it('deletes the file from storage and promotes another file', function () {
    $sentence = Sentence::factory()->for(lessonTree())->create();
    Storage::disk('local')->put('audio/a.mp3', 'a');
    Storage::disk('local')->put('audio/b.mp3', 'b');
    $first = audioFor($sentence, ['path' => 'audio/a.mp3', 'is_primary' => true]);
    $second = audioFor($sentence, ['path' => 'audio/b.mp3', 'is_primary' => false]);
    $this->actingAs(userWithRole('admin'));

    $this->delete(route('admin.audio.destroy', $first));

    Storage::disk('local')->assertMissing('audio/a.mp3');
    Storage::disk('local')->assertExists('audio/b.mp3');

    expect(SentenceAudio::withTrashed()->count())->toBe(1)
        ->and($second->fresh()->is_primary)->toBeTrue();
});

it('lists uploaded files and sentences without audio', function () {
    $lesson = lessonTree();
    $withAudio = Sentence::factory()->for($lesson)->create(['english_text' => 'Has audio.']);
    Sentence::factory()->for($lesson)->create(['english_text' => 'Needs audio.']);
    audioFor($withAudio);
    $this->actingAs(userWithRole('admin'));

    $this->get(route('admin.audio.index'))
        ->assertInertia(fn ($page) => $page
            ->component('admin/audio/index')
            ->where('view', 'files')
            ->has('items.data', 1)
            ->where('items.data.0.english_text', 'Has audio.'));

    $this->get(route('admin.audio.index', ['view' => 'missing']))
        ->assertInertia(fn ($page) => $page
            ->where('view', 'missing')
            ->has('items.data', 1)
            ->where('items.data.0.english_text', 'Needs audio.')
            ->where('items.data.0.lesson.id', $lesson->id));
});

it('forbids learners from the audio library', function () {
    $this->actingAs(userWithRole('learner'))->get(route('admin.audio.index'))->assertForbidden();
});

describe('streaming', function () {
    beforeEach(function () {
        Storage::disk('local')->put('audio/test.mp3', 'ID3 fake audio bytes');
    });

    function streamableAudio(bool $published): SentenceAudio
    {
        $sentence = Sentence::factory()->for(lessonTree($published))->create();

        return audioFor($sentence, ['path' => 'audio/test.mp3']);
    }

    it('sends guests to the login page', function () {
        $audio = streamableAudio(true);

        $this->get(route('audio.stream', $audio))->assertRedirect(route('login'));
    });

    it('lets a learner hear audio of a published lesson', function () {
        $audio = streamableAudio(true);

        $this->actingAs(userWithRole('learner'))
            ->get(route('audio.stream', $audio))
            ->assertOk()
            ->assertHeader('Content-Type', 'audio/mpeg')
            ->assertHeader('X-Content-Type-Options', 'nosniff');
    });

    it('keeps draft audio away from learners', function () {
        $audio = streamableAudio(false);

        $this->actingAs(userWithRole('learner'))->get(route('audio.stream', $audio))->assertForbidden();
    });

    it('lets staff hear draft audio', function () {
        $audio = streamableAudio(false);

        $this->actingAs(userWithRole('content-manager'))->get(route('audio.stream', $audio))->assertOk();
    });

    it('answers 404 when the file is gone', function () {
        $audio = streamableAudio(true);
        Storage::disk('local')->delete('audio/test.mp3');

        $this->actingAs(userWithRole('learner'))->get(route('audio.stream', $audio))->assertNotFound();
    });

    it('answers 404 for audio of a deleted sentence', function () {
        $audio = streamableAudio(true);
        $audio->sentence->delete();

        $this->actingAs(userWithRole('admin'))->get(route('audio.stream', $audio))->assertNotFound();
    });
});
