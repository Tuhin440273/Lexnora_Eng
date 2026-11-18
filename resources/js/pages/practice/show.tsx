import { Head, Link, router, useForm } from '@inertiajs/react';
import {
    ArrowRight,
    BookmarkPlus,
    CircleCheck,
    CircleX,
    Eye,
    EyeOff,
    Lightbulb,
    NotebookPen,
    Trophy,
    X,
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
import { useLetterSize } from '@/hooks/use-letter-size';
import { guideWords, matchGuide } from '@/lib/guide';
import { LETTER_SIZE_CLASSES } from '@/lib/letter-size';
import { describeNextReview, wordsOf } from '@/lib/learn';
import { cn } from '@/lib/utils';
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
    autoplay: boolean;
    history: boolean[];
};

function TypingCard({
    session,
    item,
    hint,
    speeds,
    defaultSpeed,
    autoplay,
}: {
    session: PracticeSessionInfo;
    item: PracticeItem;
    hint: Props['hint'];
    speeds: number[];
    defaultSpeed: number;
    autoplay: boolean;
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
    const [guideOn, setGuideOn] = useState(true);
    const [started, setStarted] = useState(false);
    const [shown, setShown] = useState(0);
    const field = useRef<HTMLTextAreaElement>(null);
    const { size } = useLetterSize();

    const words = guideWords(item.guide_text ?? '');
    const matched = matchGuide(words, form.data.answer);

    useEffect(() => {
        field.current?.focus();
    }, []);

    // One more guide word appears every second once the audio has started.
    useEffect(() => {
        if (!started || shown >= words.length) {
            return;
        }

        const timer = window.setTimeout(
            () => setShown((count) => count + 1),
            1000,
        );

        return () => window.clearTimeout(timer);
    }, [started, shown, words.length]);

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
    const hasGuide = item.guide_text !== null;

    // Alt+I asks for a hint, Alt+G shows or hides the guide text. (Alt+H and
    // Alt+S are taken by browser menus.)
    useEffect(() => {
        const onKey = (event: KeyboardEvent) => {
            if (!event.altKey) {
                return;
            }

            if (event.code === 'KeyI' && hint.allowed > 0 && hintsLeft > 0) {
                event.preventDefault();
                askHint();
            } else if (event.code === 'KeyG' && hasGuide) {
                event.preventDefault();
                setGuideOn((on) => !on);
            }
        };

        window.addEventListener('keydown', onKey);

        return () => window.removeEventListener('keydown', onKey);
    });
    const label =
        item.content_type === 'sentence'
            ? 'sentence'
            : item.content_type === 'phrase'
              ? 'phrase'
              : 'word';
    const showGuide = hasGuide && guideOn;

    return (
        <form onSubmit={submit} className="flex flex-1 flex-col">
            <div className="mx-auto my-auto flex w-full max-w-2xl flex-col items-center gap-8 px-4 py-6">
                <AudioPlayer
                    src={item.audio_url}
                    speakText={item.speak_text}
                    speeds={speeds}
                    defaultSpeed={defaultSpeed}
                    autoPlay={autoplay}
                    onStart={() => {
                        setPlays((count) => count + 1);
                        setStarted(true);
                        setShown((count) => Math.max(count, 1));
                    }}
                    onSpeedChange={setSpeed}
                />

                {item.bengali && (
                    <div className="text-center">
                        <p className="text-xs tracking-wide text-muted-foreground uppercase">
                            Meaning in Bengali
                        </p>
                        <p lang="bn" className="mt-1 text-2xl leading-relaxed">
                            {item.bengali}
                        </p>
                    </div>
                )}

                <div className="w-full space-y-3">
                    <Label
                        htmlFor="answer"
                        className="block text-center text-base font-normal text-muted-foreground"
                    >
                        Type the {label} you hear
                    </Label>

                    {hint.text && (
                        <p
                            className="mx-auto w-fit rounded-lg border border-dashed bg-muted/40 px-3 py-2 text-center font-mono text-lg tracking-wide"
                            aria-live="polite"
                        >
                            <span className="sr-only">Hint: </span>
                            {hint.text}
                        </p>
                    )}

                    <div className="border-b-2 border-input transition-colors focus-within:border-primary">
                        {showGuide && (
                            <p
                                aria-hidden
                                className={cn(
                                    'flex min-h-14 flex-wrap justify-center gap-x-2 gap-y-1 px-2 pt-2 leading-snug select-none',
                                    LETTER_SIZE_CLASSES[size].guide,
                                )}
                            >
                                {words.map((word, index) => (
                                    <span
                                        key={index}
                                        className={cn(
                                            'transition-all duration-500 motion-reduce:transition-none',
                                            matched[index]
                                                ? 'font-medium text-success'
                                                : index >= shown
                                                  ? 'opacity-0'
                                                  : 'text-muted-foreground opacity-40 blur-[1.5px]',
                                        )}
                                    >
                                        {word}
                                    </span>
                                ))}
                            </p>
                        )}
                        <Textarea
                            id="answer"
                            ref={field}
                            rows={2}
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
                            className={cn(
                                'min-h-0 resize-none rounded-none border-0 bg-transparent px-2 text-center font-practice shadow-none focus-visible:ring-0 dark:bg-transparent',
                                LETTER_SIZE_CLASSES[size].input,
                            )}
                            aria-invalid={!!form.errors.answer}
                            aria-describedby="answer-help"
                        />
                    </div>
                    <InputError
                        message={form.errors.answer}
                        className="text-center"
                    />
                    <p
                        id="answer-help"
                        className="text-center text-xs text-muted-foreground"
                    >
                        {session.mode !== 'hard' &&
                            `${item.word_count} ${item.word_count === 1 ? 'word' : 'words'}. `}
                        {showGuide &&
                            'The faint words are a guide: each one turns clear when you type it correctly.'}
                    </p>
                    <p className="hidden text-center text-xs text-muted-foreground/80 sm:block">
                        Ctrl+Enter checks · Alt+P plays · Alt+R replays
                        {hint.allowed > 0 && ' · Alt+I hint'}
                        {hasGuide && ' · Alt+G guide'}
                    </p>
                </div>
            </div>

            <div className="sticky bottom-0 border-t bg-background">
                <div className="mx-auto flex w-full max-w-2xl items-center gap-2 px-4 py-3">
                    {hasGuide && (
                        <Button
                            type="button"
                            variant="outline"
                            size="icon"
                            className="size-10 shrink-0"
                            onClick={() => setGuideOn((on) => !on)}
                            aria-pressed={guideOn}
                            aria-label="Guide text"
                            aria-keyshortcuts="Alt+G"
                            title="Guide text (Alt+G)"
                        >
                            {guideOn ? <Eye /> : <EyeOff />}
                        </Button>
                    )}
                    {hint.allowed > 0 && (
                        <Button
                            type="button"
                            variant="outline"
                            size="lg"
                            className="shrink-0"
                            onClick={askHint}
                            disabled={hintsLeft <= 0}
                            aria-keyshortcuts="Alt+I"
                        >
                            <Lightbulb />
                            Hint
                            <span className="hidden sm:inline">
                                ({Math.max(0, hintsLeft)} left)
                            </span>
                            <span className="sm:hidden">
                                {Math.max(0, hintsLeft)}
                            </span>
                        </Button>
                    )}
                    <Button
                        type="submit"
                        size="lg"
                        className="flex-1 sm:ml-auto sm:min-w-44 sm:flex-none"
                        disabled={
                            form.processing || form.data.answer.trim() === ''
                        }
                    >
                        Check answer
                    </Button>
                </div>
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
    // Every word matched exactly: the comparison would only repeat the text.
    const identical = result.tokens.every((token) => token.type === 'correct');
    const nextReview = describeNextReview(result.next_review);

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
        <div className="flex flex-1 flex-col" aria-live="polite">
            <div className="mx-auto my-auto flex w-full max-w-2xl flex-col gap-8 px-4 py-6">
                {!identical && (
                    <section className="space-y-3 text-center">
                        <h2 className="text-sm text-muted-foreground">
                            Your answer compared with the sentence
                        </h2>
                        <DiffView tokens={result.tokens} className="text-2xl" />
                        <DiffLegend className="justify-center" />
                    </section>
                )}

                <section className="space-y-1 text-center">
                    <p className="text-xs tracking-wide text-muted-foreground uppercase">
                        Correct text
                    </p>
                    <p className="text-2xl font-medium">{result.expected}</p>
                    {result.bengali && (
                        <p
                            lang="bn"
                            className="text-xl leading-relaxed text-muted-foreground"
                        >
                            {result.bengali}
                        </p>
                    )}
                    {result.explanation && (
                        <p className="pt-1 text-sm italic">
                            {result.explanation}
                        </p>
                    )}
                </section>

                {result.badges.length > 0 && (
                    <ul className="space-y-1">
                        {result.badges.map((badge) => (
                            <li
                                key={badge.name}
                                className="flex items-center justify-center gap-2 rounded-lg border border-primary/30 bg-accent p-3 text-sm"
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

                <section
                    className="space-y-3 text-center"
                    aria-label="Save to your notebooks"
                >
                    <h2 className="text-sm text-muted-foreground">
                        Save a word to your vocabulary notebook
                    </h2>
                    <ul className="flex flex-wrap justify-center gap-2">
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
                        <div className="space-y-2 text-left">
                            <Label htmlFor="result-note" className="sr-only">
                                Note
                            </Label>
                            <Textarea
                                id="result-note"
                                rows={2}
                                value={note}
                                onChange={(event) =>
                                    setNote(event.target.value)
                                }
                                placeholder="Write something to remember about this sentence"
                            />
                            <div className="flex justify-center gap-2">
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

                <p className="text-center text-xs text-muted-foreground">
                    The percentage compares your typing with the text. It does
                    not measure your pronunciation.
                </p>
            </div>

            <div className="sticky bottom-0 border-t bg-background">
                <div
                    className={cn(
                        result.is_correct ? 'bg-success/10' : 'bg-err/10',
                    )}
                >
                    <div className="mx-auto flex w-full max-w-2xl items-center gap-3 px-4 py-3">
                        {result.is_correct ? (
                            <CircleCheck className="size-8 shrink-0 text-success" />
                        ) : (
                            <CircleX className="size-8 shrink-0 text-err" />
                        )}
                        <div className="min-w-0 flex-1">
                            <p
                                className={cn(
                                    'text-lg leading-tight font-semibold',
                                    result.is_correct
                                        ? 'text-success'
                                        : 'text-err',
                                )}
                            >
                                {result.is_correct ? 'Correct' : 'Not quite'}
                            </p>
                            <p className="text-sm text-muted-foreground">
                                Text match {result.accuracy}%
                                {nextReview && ` · ${nextReview}`}
                            </p>
                        </div>
                        {result.xp > 0 && <Badge>+{result.xp} XP</Badge>}
                        {result.finished ? (
                            <Button size="lg" asChild>
                                <Link
                                    href={PracticeController.summary(sessionId)}
                                >
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
            </div>
        </div>
    );
}

function LetterSizeControls() {
    const { size, bigger, smaller } = useLetterSize();
    const change = (action: () => void) => () => {
        action();
        document.getElementById('answer')?.focus();
    };

    return (
        <div
            role="group"
            aria-label="Letter size"
            className="flex items-center"
        >
            <Button
                type="button"
                variant="ghost"
                size="sm"
                onClick={change(smaller)}
                disabled={size === 'normal'}
                aria-label="Smaller letters"
            >
                A−
            </Button>
            <Button
                type="button"
                variant="ghost"
                size="sm"
                onClick={change(bigger)}
                disabled={size === 'larger'}
                aria-label="Bigger letters"
            >
                A+
            </Button>
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
    autoplay,
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
            <div className="flex min-h-dvh flex-col bg-background">
                <header className="mx-auto w-full max-w-2xl px-4 pt-4">
                    <h1 className="sr-only">
                        Practice: {session.title}, item {session.position} of{' '}
                        {session.total}
                    </h1>
                    <div className="flex items-center gap-3">
                        <Button
                            variant="ghost"
                            size="icon"
                            asChild
                            className="shrink-0"
                        >
                            <Link
                                href={backHref}
                                aria-label="Leave practice"
                                title="Leave practice"
                            >
                                <X />
                            </Link>
                        </Button>
                        <div
                            role="progressbar"
                            aria-valuemin={0}
                            aria-valuemax={100}
                            aria-valuenow={percent}
                            aria-label="Practice progress"
                            className="h-2.5 flex-1 overflow-hidden rounded-full bg-muted"
                        >
                            <div
                                className="h-full rounded-full bg-primary transition-[width] motion-reduce:transition-none"
                                style={{ width: `${percent}%` }}
                            />
                        </div>
                        <span className="text-sm text-muted-foreground tabular-nums">
                            {Math.min(session.position, session.total)} /{' '}
                            {session.total}
                        </span>
                        {!visibleResult && item && <LetterSizeControls />}
                    </div>
                    <p className="mt-2 text-center text-sm text-muted-foreground capitalize">
                        {session.title} ·{' '}
                        {session.kind === 'lesson'
                            ? `${session.mode} mode`
                            : session.kind}
                    </p>
                    {history.length > 0 && (
                        <ul
                            className="mt-2 flex flex-wrap justify-center gap-1"
                            aria-label="Results so far"
                        >
                            {history.map((correct, index) => (
                                <li
                                    key={index}
                                    className={cn(
                                        'size-2 rounded-full',
                                        correct ? 'bg-success' : 'bg-err',
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

                {visibleResult ? (
                    <ResultCard
                        result={visibleResult}
                        sessionId={session.id}
                        onNext={() => setDismissedId(visibleResult.attempt_id)}
                    />
                ) : item ? (
                    <TypingCard
                        key={item.id}
                        session={session}
                        item={item}
                        hint={hint}
                        speeds={speeds}
                        defaultSpeed={default_speed}
                        autoplay={autoplay}
                    />
                ) : null}
            </div>
        </>
    );
}
