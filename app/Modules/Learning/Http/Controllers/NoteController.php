<?php

namespace App\Modules\Learning\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Courses\Models\Sentence;
use App\Modules\Courses\Support\LessonAccess;
use App\Modules\Learning\Http\Requests\NoteRequest;
use App\Modules\Learning\Models\UserNote;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class NoteController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('search', ''));

        $notes = UserNote::where('user_id', $request->user()->id)
            ->with('sentence:id,english_text')
            ->when($search !== '', function ($q) use ($search) {
                $like = '%'.addcslashes($search, '%_\\').'%';
                $q->where(fn ($q) => $q->where('body', 'like', $like)->orWhere('title', 'like', $like));
            })
            ->latest('id')
            ->paginate(12)
            ->withQueryString()
            ->through(fn (UserNote $n) => [
                'id' => $n->id,
                'title' => $n->title,
                'body' => $n->body,
                'sentence' => $n->sentence?->english_text,
                'created_at' => $n->created_at->toIso8601String(),
            ]);

        return Inertia::render('notes/index', ['notes' => $notes, 'search' => $search]);
    }

    public function store(NoteRequest $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validated();

        $sentence = ! empty($data['sentence_id']) ? Sentence::with('lesson')->find($data['sentence_id']) : null;
        $sentence = $sentence && $sentence->lesson && LessonAccess::canViewLesson($user, $sentence->lesson) ? $sentence : null;

        UserNote::create([
            'user_id' => $user->id,
            'sentence_id' => $sentence?->id,
            'title' => $data['title'] ?? null,
            'body' => $data['body'],
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Note saved.')]);

        return back();
    }

    public function update(NoteRequest $request, UserNote $note): RedirectResponse
    {
        Gate::authorize('update', $note);

        $note->update([
            'title' => $request->validated('title'),
            'body' => $request->validated('body'),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Note updated.')]);

        return back();
    }

    public function destroy(UserNote $note): RedirectResponse
    {
        Gate::authorize('delete', $note);

        $note->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Note deleted.')]);

        return back();
    }
}
