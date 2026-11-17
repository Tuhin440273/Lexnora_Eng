import { Head, Link, usePage } from '@inertiajs/react';
import { BookMarked, CircleAlert, Flame, RotateCw } from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import { DiffLegend, DiffView } from '@/components/diff-view';
import { FloatingWords } from '@/components/floating-words';
import { Button } from '@/components/ui/button';
import { dashboard, login, register } from '@/routes';
import type { DiffToken } from '@/types';

const EXAMPLE: DiffToken[] = [
    { type: 'correct', expected: 'I', typed: 'I' },
    { type: 'correct', expected: 'would', typed: 'would' },
    { type: 'correct', expected: 'like', typed: 'like' },
    { type: 'missing', expected: 'a' },
    { type: 'correct', expected: 'cup', typed: 'cup' },
    { type: 'correct', expected: 'of', typed: 'of' },
    { type: 'incorrect', expected: 'coffee,', typed: 'cofee' },
    { type: 'correct', expected: 'please.', typed: 'please' },
];

const STEPS = [
    [
        'Listen',
        'Play a short recording. Slow it down to 0.5x or speed it up to 2x.',
    ],
    [
        'Understand',
        'Read the Bengali translation after you answer, and the explanation when there is one.',
    ],
    [
        'Type',
        'Write what you heard. No multiple choice, so you cannot guess your way through.',
    ],
    ['Check', 'See every word you got right, wrong or missed, side by side.'],
    ['Learn', 'Save words you did not know and add notes to sentences.'],
    [
        'Review',
        'Sentences come back after longer and longer breaks, until you know them.',
    ],
] as const;

const MODES = [
    [
        'Easy',
        'Words first, then phrases, then the whole sentence. Up to three hints.',
    ],
    ['Medium', 'Short phrases first, then the whole sentence. One hint.'],
    ['Hard', 'Whole sentences only and no hints, like real listening.'],
] as const;

const KEEPS: { icon: LucideIcon; title: string; text: string }[] = [
    {
        icon: BookMarked,
        title: 'Vocabulary notebook',
        text: 'Save words with the sentence you met them in.',
    },
    {
        icon: CircleAlert,
        title: 'Mistakes notebook',
        text: 'Every wrong sentence is kept until you type it right.',
    },
    {
        icon: RotateCw,
        title: 'Review',
        text: 'Sentences and words return when you are about to forget them.',
    },
    {
        icon: Flame,
        title: 'Streak and XP',
        text: 'A daily check-in, badges and a leaderboard you can hide from.',
    },
];

const LEVELS = ['A1', 'A2', 'B1', 'B2', 'C1', 'C2'];

export default function Welcome() {
    const { auth, name } = usePage().props;
    const loggedIn = Boolean(auth.user);

    return (
        <>
            <Head title="Learn English by typing what you hear" />
            <a
                href="#main"
                className="sr-only focus:not-sr-only focus:absolute focus:top-2 focus:left-2 focus:z-50 focus:rounded-md focus:bg-background focus:px-3 focus:py-2"
            >
                Skip to the content
            </a>

            <div className="relative min-h-screen overflow-hidden bg-background text-foreground">
                <FloatingWords className="inset-x-0 top-0 bottom-auto h-[40rem]" />
                <header className="relative mx-auto flex max-w-6xl items-center justify-between px-5 py-5">
                    <span className="text-lg font-bold tracking-tight">
                        {name}
                    </span>
                    <nav
                        aria-label="Account"
                        className="flex items-center gap-2"
                    >
                        {loggedIn ? (
                            <Button asChild>
                                <Link href={dashboard()}>Dashboard</Link>
                            </Button>
                        ) : (
                            <>
                                <Button variant="ghost" asChild>
                                    <Link href={login()}>Log in</Link>
                                </Button>
                                <Button asChild>
                                    <Link href={register()}>
                                        Create account
                                    </Link>
                                </Button>
                            </>
                        )}
                    </nav>
                </header>

                <main id="main" className="relative">
                    <section className="mx-auto grid max-w-6xl items-center gap-10 px-5 pt-10 pb-16 lg:grid-cols-2 lg:pt-16">
                        <div className="space-y-6">
                            <h1 className="max-w-xl animate-rise text-4xl font-semibold tracking-tight text-balance sm:text-6xl">
                                Learn English by typing what you hear.
                            </h1>
                            <p
                                className="max-w-lg animate-rise text-lg text-muted-foreground"
                                style={{ animationDelay: '120ms' }}
                            >
                                Short recordings, one sentence at a time. You
                                type, and {name} shows you exactly which words
                                you caught and which ones slipped past.
                            </p>
                            <div className="flex flex-wrap gap-3">
                                <Button size="lg" asChild>
                                    <Link
                                        href={
                                            loggedIn ? dashboard() : register()
                                        }
                                    >
                                        {loggedIn
                                            ? 'Continue learning'
                                            : 'Start for free'}
                                    </Link>
                                </Button>
                                {!loggedIn && (
                                    <Button size="lg" variant="outline" asChild>
                                        <Link href={login()}>
                                            I already have an account
                                        </Link>
                                    </Button>
                                )}
                            </div>
                            <p className="text-sm text-muted-foreground">
                                From A1 beginner to C2 proficiency. Translations
                                in Bengali.
                            </p>
                        </div>

                        <figure
                            className="animate-pop space-y-4 rounded-3xl border bg-card p-6 shadow-xl"
                            style={{ animationDelay: '200ms' }}
                            aria-labelledby="example-caption"
                        >
                            <figcaption
                                id="example-caption"
                                className="text-sm font-medium text-muted-foreground"
                            >
                                What you see after you answer (an example)
                            </figcaption>
                            <p className="text-sm text-muted-foreground">
                                You typed:{' '}
                                <span className="font-mono">
                                    I would like cup of cofee please
                                </span>
                            </p>
                            <DiffView tokens={EXAMPLE} className="text-xl" />
                            <DiffLegend />
                            <div className="flex items-center justify-between rounded-lg bg-muted/50 p-3 text-sm">
                                <span
                                    lang="bn"
                                    className="text-muted-foreground"
                                >
                                    আমি এক কাপ কফি চাই, দয়া করে।
                                </span>
                                <span className="font-semibold tabular-nums">
                                    Text match 91%
                                </span>
                            </div>
                        </figure>
                    </section>

                    <section
                        className="border-y bg-muted/30"
                        aria-labelledby="how"
                    >
                        <div className="mx-auto max-w-6xl px-5 py-14">
                            <h2
                                id="how"
                                className="text-2xl font-bold tracking-tight"
                            >
                                How one sentence works
                            </h2>
                            <ol className="mt-8 grid gap-x-8 gap-y-6 sm:grid-cols-2 lg:grid-cols-3">
                                {STEPS.map(([title, text], index) => (
                                    <li key={title} className="flex gap-4">
                                        <span
                                            aria-hidden
                                            className="flex size-8 shrink-0 items-center justify-center rounded-full bg-primary text-sm font-semibold text-primary-foreground"
                                        >
                                            {index + 1}
                                        </span>
                                        <div>
                                            <h3 className="font-semibold">
                                                {title}
                                            </h3>
                                            <p className="text-sm text-muted-foreground">
                                                {text}
                                            </p>
                                        </div>
                                    </li>
                                ))}
                            </ol>
                        </div>
                    </section>

                    <section
                        className="mx-auto max-w-6xl px-5 py-14"
                        aria-labelledby="modes"
                    >
                        <h2
                            id="modes"
                            className="text-2xl font-bold tracking-tight"
                        >
                            Three ways to practise
                        </h2>
                        <ul className="mt-8 grid gap-4 md:grid-cols-3">
                            {MODES.map(([title, text]) => (
                                <li
                                    key={title}
                                    className="rounded-xl border bg-card p-5"
                                >
                                    <h3 className="font-semibold">{title}</h3>
                                    <p className="mt-1 text-sm text-muted-foreground">
                                        {text}
                                    </p>
                                </li>
                            ))}
                        </ul>

                        <ul
                            aria-label="Levels"
                            className="mt-8 flex flex-wrap items-center gap-2"
                        >
                            {LEVELS.map((level) => (
                                <li
                                    key={level}
                                    className="rounded-md border px-3 py-1 text-sm font-medium"
                                >
                                    {level}
                                </li>
                            ))}
                        </ul>
                    </section>

                    <section
                        className="mx-auto max-w-6xl px-5 pb-14"
                        aria-labelledby="keep"
                    >
                        <h2
                            id="keep"
                            className="text-2xl font-bold tracking-tight"
                        >
                            What stays with you
                        </h2>
                        <ul className="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                            {KEEPS.map((item) => (
                                <li key={item.title} className="space-y-2">
                                    <item.icon
                                        className="size-6 text-primary"
                                        aria-hidden
                                    />
                                    <h3 className="font-semibold">
                                        {item.title}
                                    </h3>
                                    <p className="text-sm text-muted-foreground">
                                        {item.text}
                                    </p>
                                </li>
                            ))}
                        </ul>
                    </section>

                    {!loggedIn && (
                        <section className="border-t bg-muted/30">
                            <div className="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-4 px-5 py-12">
                                <h2 className="text-2xl font-bold tracking-tight">
                                    Ready for your first sentence?
                                </h2>
                                <Button size="lg" asChild>
                                    <Link href={register()}>
                                        Create your account
                                    </Link>
                                </Button>
                            </div>
                        </section>
                    )}
                </main>

                <footer className="mx-auto max-w-6xl px-5 py-8 text-sm text-muted-foreground">
                    {name}. The percentage compares your typing with the text.
                    It does not measure pronunciation.
                </footer>
            </div>
        </>
    );
}
