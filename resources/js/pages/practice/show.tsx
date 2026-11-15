import { Head, Link, router, useForm } from '@inertiajs/react';
import {
    ArrowRight,
    BookmarkPlus,
    CircleCheck,
    CircleX,
    Lightbulb,
    NotebookPen,
    Trophy,
} from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import type { FormEvent } from 'react';
import PracticeController from '@/actions/App/Modules/Dictation/Http/Controllers/PracticeController';
import NoteController from '@/actions/App/Modules/Learning/Http/Controllers/NoteController';
import SavedVocabularyController from '@/actions/App/Modules/Vocabulary/Http/Controllers/SavedVocabularyController';
import { AudioPlayer } from '@/components/audio-player';
import { DiffLegend, DiffView } from '@/components/diff-view';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { cn } from '@/lib/utils';
import { wordsOf } from '@/lib/learn';
import type {
    PracticeItem,
    PracticeResult,
    PracticeSessionInfo,
} from '@/types';

type Props = {
    session: PracticeSessionInfo;
    item: PracticeItem | null;
    hint: { allowed: number; used: number; text: string | null };
    result: PracticeResult | null;
    speeds: number[];
    default_speed: number;
    history: boolean[];
};

function TypingCard({
    session,
    item,
    hint,
    speeds,
    defaultSpeed,
}: {
    session: PracticeSessionInfo;
    item: PracticeItem;
    hint: Props['hint'];
    speeds: number[];
    defaultSpeed: number;
}) {
    const form = useForm({
        answer: '',
        replay_count: 0,
        playback_speed: defaultSpeed,
        time_spent_ms: 0,
    });
    const [plays, setPlays] = useState(0);
    const [speed, setSpeed] = useState(defaultSpeed);
    const [startedAt] = useState(() => Date.now());
    const field = useRef<HTMLTextAreaElement>(null);

    useEffect(() => {
        field.current?.focus();
    }, []);

    const submit = (event?: FormEvent) => {
        event?.preventDefault();

        form.transform((data) => ({
            ...data,
            replay_count: Math.max(0, plays - 1),
            playback_speed: speed,
            time_spent_ms: Date.now() - startedAt,
        }));

        form.post(PracticeController.answer.url(session.id), {
            preserveScroll: true,
            onSuccess: () => form.reset('answer'),
        });
    };

    const askHint = () =>
        router.post(
            PracticeController.hint.url(session.id),
            {},
            { preserveScroll: true, preserveState: true },
        );

    const hintsLeft = hint.allowed - hint.used;
    const label =
        item.content_type === 'sentence'
            ? 'sentence'
            : item.content_type === 'phrase'
              ? 'phrase'
              : 'word';

    return (
        <form onSubmit={submit} className="space-y-5">
            <AudioPlayer
                src={item.audio_url}
                speakText={item.speak_text}
                speeds={speeds}
                defaultSpeed={defaultSpeed}
                onStart={() => setPlays((count) => count + 1)}
                onSpeedChange={setSpeed}
            />

            <div className="grid gap-2">
                <Label htmlFor="answer" className="text-base">
                    Type the {label} you hear
                </Label>
                <Textarea
                    id="answer"
                    ref={field}
                    rows={3}
                    value={form.data.answer}
                    onChange={(event) =>
                        form.setData('answer', event.target.value)
                    }
                    onKeyDown={(event) => {
                        if (
                            event.key === 'Enter' &&
                            (event.ctrlKey || event.metaKey)
                        ) {
                            submit();
                        }
                    }}
                    maxLength={1000}
                    autoComplete="off"
                    autoCorrect="off"
                    autoCapitalize="off"
                    spellCheck={false}
                    className="text-lg"
                    aria-invalid={!!form.errors.answer}
                    aria-describedby="answer-help"
                />
                <InputError message={form.errors.answer} />
                <p id="answer-help" className="text-xs text-muted-foreground">
                    {session.mode !== 'hard' &&
                        `${item.word_count} ${item.word_count === 1 ? 'word' : 'words'}. `}
                    Press Ctrl+Enter to check. Alt+P plays or pauses, Alt+R
                    replays.
                </p>
            </div>

            {hint.text && (
                <p
                    className="rounded-lg border border-dashed bg-muted/40 p-3 font-mono text-lg tracking-wide"
                    aria-live="polite"
                >
                    <span className="sr-only">Hint: </span>
                    {hint.text}
                </p>
            )}

            <div className="flex flex-wrap items-center gap-3">
                <Button
                    type="submit"
                    size="lg"
                    disabled={form.processing || form.data.answer.trim() === ''}
                >
                    Check answer
                </Button>
                {hint.allowed > 0 && (
                    <Button
                        type="button"
                        variant="outline"
                        size="lg"
                        onClick={askHint}
                        disabled={hintsLeft <= 0}
                    >
                        <Lightbulb />
                        Hint ({Math.max(0, hintsLeft)} left)
                    </Button>
                )}
            </div>
        </form>
    );
}

function ResultCard({
    result,
    sessionId,
    onNext,
}: {
    result: PracticeResult;
    sessionId: string;
    onNext: () => void;
}) {
    const nextButton = useRef<HTMLButtonElement>(null);
    const [noteOpen, setNoteOpen] = useState(false);

    useEffect(() => {
        nextButton.current?.focus();
    }, []);

    const [note, setNote] = useState('');
    const [saved, setSaved] = useState<string[]>([]);

    const saveWord = (term: string) => {
        router.post(
            SavedVocabularyController.store.url(),
            { term, context_sentence_id: result.sentence_id },
            {
                preserveScroll: true,
                onSuccess: () => setSaved((s) => [...s, term]),
            },
        );
    };

    const saveNote = () => {
        router.post(
            NoteController.store.url(),
            { body: note, sentence_id: result.sentence_id },
            {
                preserveScroll: true,
                onSuccess: () => {
                    setNote('');
                    setNoteOpen(false);
                },
            },
        );
    };

    return (
        <div className="space-y-5" aria-live="polite">
            <div
                className={cn(
                    'flex flex-wrap items-center justify-between gap-3 rounded-xl border p-4',
                    result.is_correct
                        ? 'border-success/30 bg-success/10'
                        : 'border-destructive/30 bg-destructive/10',
                )}
            >
                <div className="flex items-center gap-3">
                    {result.is_correct ? (
                        <CircleCheck className="size-8 text-success" />
                    ) : (
                        <CircleX className="size-8 text-destructive" />
                    )}
                    <div>
                        <p className="text-lg font-semibold">
                            {result.is_correct ? 'Correct' : 'Not quite'}
                        </p>
                        <p className="text-sm text-muted-foreground">
                            Text match {result.accuracy}%
                        </p>
                    </div>
                </div>
                {result.xp > 0 && <Badge>+{result.xp} XP</Badge>}
            </div>

            <div className="space-y-2">
                <h2 className="text-sm font-medium text-muted-foreground">
                    Your answer compared with the sentence
                </h2>
                <DiffView tokens={result.tokens} />
                <DiffLegend />
            </div>

            <div className="space-y-1 rounded-lg bg-muted/40 p-4">
                <p className="text-sm text-muted-foreground">Correct text</p>
                <p className="text-lg font-medium">{result.expected}</p>
                {result.bengali && (
                    <p lang="bn" className="text-muted-foreground">
                        {result.bengali}
                    </p>
                )}
                {result.explanation && (
                    <p className="pt-1 text-sm italic">{result.explanation}</p>
                )}
            </div>

            {result.badges.length > 0 && (
                <ul className="space-y-1">
                    {result.badges.map((badge) => (
                        <li
                            key={badge.name}
                            className="flex items-center gap-2 rounded-lg border border-primary/30 bg-accent p-3 text-sm"
                        >
                            <Trophy className="size-4 text-primary" />
                            <span>
                                New badge: <strong>{badge.name}</strong>
                                {badge.xp_reward > 0 &&
                                    ` (+${badge.xp_reward} XP)`}
                            </span>
                        </li>
                    ))}
                </ul>
            )}

            <section className="space-y-2" aria-label="Save to your notebooks">
                <h2 className="text-sm font-medium text-muted-foreground">
                    Save a word to your vocabulary notebook
                </h2>
                <ul className="flex flex-wrap gap-2">
                    {wordsOf(result.expected).map((word) => (
                        <li key={word}>
                            <Button
                                type="button"
                                size="sm"
                                variant="outline"
                                disabled={saved.includes(word)}
                                onClick={() => saveWord(word)}
                            >
                                <BookmarkPlus /> {word}
                            </Button>
                        </li>
                    ))}
                </ul>
                {!noteOpen ? (
                    <Button
                        type="button"
                        size="sm"
                        variant="ghost"
                        onClick={() => setNoteOpen(true)}
                    >
                        <NotebookPen /> Add a note
                    </Button>
                ) : (
                    <div className="space-y-2">
                        <Label htmlFor="result-note" className="sr-only">
                            Note
                        </Label>
                        <Textarea
                            id="result-note"
                            rows={2}
                            value={note}
                            onChange={(event) => setNote(event.target.value)}
                            placeholder="Write something to remember about this sentence"
                        />
                        <div className="flex gap-2">
                            <Button
                                type="button"
                                size="sm"
                                onClick={saveNote}
                                disabled={note.trim() === ''}
                            >
                                Save note
                            </Button>
                            <Button
                                type="button"
                                size="sm"
                                variant="ghost"
                                onClick={() => setNoteOpen(false)}
                            >
                                Cancel
                            </Button>
                        </div>
                    </div>
                )}
            </section>

            <div className="flex justify-end">
                {result.finished ? (
                    <Button size="lg" asChild>
                        <Link href={PracticeController.summary(sessionId)}>
                            See summary <ArrowRight />
                        </Link>
                    </Button>
                ) : (
                    <Button size="lg" onClick={onNext} ref={nextButton}>
                        Next <ArrowRight />
                    </Button>
                )}
            </div>
        </div>
    );
}

export default function PracticeShow({
    session,
    item,
    hint,
    result,
    speeds,
    default_speed,
    history,
}: Props) {
    const [dismissedId, setDismissedId] = useState<string | null>(null);
    const visibleResult =
        result && result.attempt_id !== dismissedId ? result : null;

    const backHref = session.back_lesson_id
        ? `/lessons/${session.back_lesson_id}`
        : '/dashboard';
    const percent = Math.round(
        ((session.finished ? session.total : session.position - 1) /
            Math.max(1, session.total)) *
            100,
    );

    return (
        <>
            <Head title={`Practice: ${session.title}`} />
            <div className="mx-auto flex w-full max-w-3xl flex-col gap-6 p-4">
                <header className="space-y-3">
                    <div className="flex flex-wrap items-center justify-between gap-2">
                        <div className="min-w-0">
                            <Link
                                href={backHref}
                                className="text-sm text-muted-foreground hover:text-foreground"
                            >
                                {session.title}
                            </Link>
                            <h1 className="text-xl font-semibold">
                                Item {session.position} of {session.total}
                            </h1>
                        </div>
                        <Badge variant="secondary" className="capitalize">
                            {session.kind === 'lesson'
                                ? `${session.mode} mode`
                                : session.kind}
                        </Badge>
                    </div>
                    <div
                        role="progressbar"
                        aria-valuemin={0}
                        aria-valuemax={100}
                        aria-valuenow={percent}
                        aria-label="Practice progress"
                        className="h-2 overflow-hidden rounded-full bg-muted"
                    >
                        <div
                            className="h-full rounded-full bg-primary transition-[width]"
                            style={{ width: `${percent}%` }}
                        />
                    </div>
                    {history.length > 0 && (
                        <ul
                            className="flex flex-wrap gap-1"
                            aria-label="Results so far"
                        >
                            {history.map((correct, index) => (
                                <li
                                    key={index}
                                    className={cn(
                                        'size-2.5 rounded-full',
                                        correct
                                            ? 'bg-success'
                                            : 'bg-destructive',
                                    )}
                                >
                                    <span className="sr-only">
                                        {correct ? 'Correct' : 'Incorrect'}
                                    </span>
                                </li>
                            ))}
                        </ul>
                    )}
                </header>

                <section className="rounded-xl border bg-card p-5">
                    {visibleResult ? (
                        <ResultCard
                            result={visibleResult}
                            sessionId={session.id}
                            onNext={() =>
                                setDismissedId(visibleResult.attempt_id)
                            }
                        />
                    ) : item ? (
                        <TypingCard
                            key={item.id}
                            session={session}
                            item={item}
                            hint={hint}
                            speeds={speeds}
                            defaultSpeed={default_speed}
                        />
                    ) : null}
                </section>

                <p className="text-xs text-muted-foreground">
                    The percentage compares your typing with the text. It does
                    not measure your pronunciation.
                </p>
            </div>
        </>
    );
}
