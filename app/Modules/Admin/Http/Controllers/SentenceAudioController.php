<?php

namespace App\Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Admin\Http\Requests\AudioUploadRequest;
use App\Modules\Admin\Services\AudioService;
use App\Modules\Courses\Models\Sentence;
use App\Modules\Courses\Models\SentenceAudio;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class SentenceAudioController extends Controller
{
    public function __construct(private readonly AudioService $audioFiles) {}

    public function store(AudioUploadRequest $request, Sentence $sentence): RedirectResponse
    {
        $this->audioFiles->store(
            $sentence,
            $request->file('audio'),
            $request->user(),
            $request->boolean('make_primary'),
            $request->filled('duration_ms') ? $request->integer('duration_ms') : null,
            $request->input('voice_label'),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Audio uploaded.')]);

        return back();
    }

    public function makePrimary(SentenceAudio $audio): RedirectResponse
    {
        Gate::authorize('manageAudio', $audio);

        $this->audioFiles->makePrimary($audio);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Primary audio changed.')]);

        return back();
    }

    public function destroy(SentenceAudio $audio): RedirectResponse
    {
        Gate::authorize('manageAudio', $audio);

        $this->audioFiles->delete($audio);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Audio deleted.')]);

        return back();
    }
}
