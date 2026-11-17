import { Head, Link, router, usePage } from '@inertiajs/react';
import {
    BookCheck,
    BookOpen,
    ChevronRight,
    Clock,
    Flame,
    GraduationCap,
    Play,
    RotateCw,
    Sparkles,
    Target,
    Trophy,
} from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import CatalogController from '@/actions/App/Modules/Courses/Http/Controllers/CatalogController';
import CheckinController from '@/actions/App/Modules/Gamification/Http/Controllers/CheckinController';
import PracticeController from '@/actions/App/Modules/Dictation/Http/Controllers/PracticeController';
import ReviewController from '@/actions/App/Modules/Review/Http/Controllers/ReviewController';
import { Button } from '@/components/ui/button';
import { WeeklyChart } from '@/components/weekly-chart';
import type { WeekDay } from '@/components/weekly-chart';
import { formatDate, formatPercent, formatStudyTime } from '@/lib/learn';
import { cn } from '@/lib/utils';
import { dashboard } from '@/routes';

type Props = {
    stats: {
        study_seconds: number;
        completed_lessons: number;
        sessions: number;
        average_accuracy: number | null;
        streak: number;
        vocabulary_learned: number;
        review_due: number;
        xp: number;
    };
    today: {
        minutes: number;
        sentences: number;
        goal_minutes: number;
        goal_sentences: number;
        checked_in: boolean;
    };
    week: WeekDay[];
    recent: {
        id: string;
        title: string;
        mode: string;
        accuracy: number;
        correct: number;
        total: number;
        completed_at: string | null;
    }[];
    continue: { id: string; title: string; done: number; total: number } | null;
    badges: { name: string; description: string | null; icon: string | null }[];
};

function GoalRing({
    label,
    value,
    goal,
}: {
    label: string;
    value: number;
    goal: number;
}) {
    const percent = Math.min(
        100,
        Math.round((value / Math.max(1, goal)) * 100),
    );
    const radius = 40;
    const circumference = 2 * Math.PI * radius;

    return (
        <div className="flex flex-col items-center gap-2">
            <div
                role="progressbar"
                aria-label={label}
                aria-valuemin={0}
                aria-valuemax={100}
                aria-valuenow={percent}
                className="relative size-28"
            >
                <svg viewBox="0 0 100 100" className="size-full -rotate-90">
                    <circle
                        cx="50"
                        cy="50"
                        r={radius}
                        fill="none"
                        strokeWidth="10"
                        className="stroke-muted"
                    />
                    <circle
                        cx="50"
                        cy="50"
                        r={radius}
                        fill="none"
                        strokeWidth="10"
                        strokeLinecap="round"
                        strokeDasharray={circumference}
                        strokeDashoffset={circumference * (1 - percent / 100)}
                        className={cn(
                            'transition-[stroke-dashoffset] motion-reduce:transition-none',
                            percent >= 100
                                ? 'stroke-success'
                                : 'stroke-primary',
                        )}
                    />
                </svg>
                <div className="absolute inset-0 flex flex-col items-center justify-center">
                    <span className="stat-figure text-2xl leading-none font-semibold">
                        {value}
                    </span>
                    <span className="mt-1 text-xs text-muted-foreground">
                        of {goal}
                    </span>
                </div>
            </div>
            <span className="text-sm font-medium">{label}</span>
        </div>
    );
}

function Highlight({
    icon: Icon,
    label,
    value,
    tone,
}: {
    icon: LucideIcon;
    label: string;
    value: string;
    tone: string;
}) {
    return (
        <div className="flex items-center gap-3 rounded-2xl border bg-card p-3 sm:gap-4 sm:p-4">
            <span
                className={cn(
                    'flex size-10 shrink-0 items-center justify-center rounded-xl sm:size-12',
                    tone,
                )}
            >
                <Icon className="size-6" aria-hidden />
            </span>
            <div className="min-w-0">
                <dt className="text-sm leading-tight text-muted-foreground">
                    {label}
                </dt>
                <dd className="stat-figure text-3xl leading-tight font-semibold">
                    {value}
                </dd>
            </div>
        </div>
    );
}

function percentTone(accuracy: number): string {
    if (accuracy >= 90) {
        return 'bg-success/15 text-success';
    }

    return accuracy >= 60 ? 'bg-star/15 text-star' : 'bg-err/10 text-err';
}

export default function Dashboard({
    stats,
    today,
    week,
    recent,
    continue: resume,
    badges,
}: Props) {
    const { auth } = usePage().props;
    const firstName = auth.user.name.split(' ')[0];

    const totals: { icon: LucideIcon; label: string; value: string }[] = [
        {
            icon: Clock,
            label: 'Study time',
            value: formatStudyTime(stats.study_seconds),
        },
        {
            icon: GraduationCap,
            label: 'Lessons completed',
            value: String(stats.completed_lessons),
        },
        {
            icon: Play,
            label: 'Practice sessions',
            value: String(stats.sessions),
        },
        {
            icon: Target,
            label: 'Average text match',
            value: formatPercent(stats.average_accuracy),
        },
    ];

    return (
        <>
            <Head title="Dashboard" />
            <div className="mx-auto flex w-full max-w-6xl flex-col gap-6 p-4 md:p-6">
                <header className="flex flex-wrap items-center justify-between gap-3">
                    <div className="min-w-0 space-y-1">
                        <h1 className="text-3xl font-semibold tracking-tight">
                            Welcome back, {firstName}
                        </h1>
                        <p className="text-muted-foreground">
                            {stats.streak > 1
                                ? `${stats.streak} days in a row. Keep it going.`
                                : 'A little listening and typing every day adds up.'}
                        </p>
                    </div>
                    <div className="flex flex-wrap items-center gap-2">
                        {stats.review_due > 0 && (
                            <Button variant="outline" asChild>
                                <Link href={ReviewController.index()}>
                                    <RotateCw /> {stats.review_due} to review
                                </Link>
                            </Button>
                        )}
                        <Button
                            variant={today.checked_in ? 'outline' : 'default'}
                            disabled={today.checked_in}
                            onClick={() =>
                                router.post(
                                    CheckinController.url(),
                                    {},
                                    { preserveScroll: true },
                                )
                            }
                        >
                            <Flame />{' '}
                            {today.checked_in
                                ? 'Checked in today'
                                : 'Daily check-in'}
                        </Button>
                    </div>
                </header>

                <section
                    aria-label="Continue learning"
                    className="rounded-3xl border border-primary/20 bg-linear-to-br from-primary/15 via-primary/5 to-transparent p-6 md:p-8"
                >
                    <div className="flex flex-wrap items-center justify-between gap-6">
                        {resume ? (
                            <>
                                <div className="min-w-60 flex-1 space-y-3">
                                    <p className="text-sm font-medium text-primary">
                                        Continue where you stopped
                                    </p>
                                    <h2 className="text-2xl font-semibold">
                                        {resume.title}
                                    </h2>
                                    <div className="flex max-w-md items-center gap-3">
                                        <div
                                            role="progressbar"
                                            aria-label="Lesson progress"
                                            aria-valuemin={0}
                                            aria-valuemax={100}
                                            aria-valuenow={Math.round(
                                                (resume.done /
                                                    Math.max(1, resume.total)) *
                                                    100,
                                            )}
                                            className="h-2.5 flex-1 overflow-hidden rounded-full bg-background/70"
                                        >
                                            <div
                                                className="h-full rounded-full bg-primary"
                                                style={{
                                                    width: `${Math.round((resume.done / Math.max(1, resume.total)) * 100)}%`,
                                                }}
                                            />
                                        </div>
                                        <span className="text-sm text-muted-foreground tabular-nums">
                                            {resume.done} of {resume.total} done
                                        </span>
                                    </div>
                                </div>
                                <Button
                                    size="lg"
                                    asChild
                                    className="h-12 w-full rounded-full px-8 text-base sm:w-auto"
                                >
                                    <Link
                                        href={PracticeController.show(
                                            resume.id,
                                        )}
                                    >
                                        <Play className="fill-current" />{' '}
                                        Continue
                                    </Link>
                                </Button>
                            </>
                        ) : (
                            <>
                                <div className="min-w-60 flex-1 space-y-2">
                                    <p className="text-sm font-medium text-primary">
                                        Ready when you are
                                    </p>
                                    <h2 className="text-2xl font-semibold">
                                        Pick a lesson and start listening
                                    </h2>
                                    <p className="text-muted-foreground">
                                        Listen, type what you hear, and see how
                                        close you got.
                                    </p>
                                </div>
                                <Button
                                    size="lg"
                                    asChild
                                    className="h-12 w-full rounded-full px-8 text-base sm:w-auto"
                                >
                                    <Link href={CatalogController.index()}>
                                        <BookOpen /> Browse courses
                                    </Link>
                                </Button>
                            </>
                        )}
                    </div>
                </section>

                <dl className="grid grid-cols-2 gap-3 lg:grid-cols-4">
                    <Highlight
                        icon={Flame}
                        label="Day streak"
                        value={String(stats.streak)}
                        tone="bg-star/15 text-star"
                    />
                    <Highlight
                        icon={Sparkles}
                        label="Total XP"
                        value={String(stats.xp)}
                        tone="bg-primary/10 text-primary"
                    />
                    <Highlight
                        icon={BookCheck}
                        label="Words learned"
                        value={String(stats.vocabulary_learned)}
                        tone="bg-success/15 text-success"
                    />
                    <Highlight
                        icon={RotateCw}
                        label="Reviews due"
                        value={String(stats.review_due)}
                        tone="bg-accent text-accent-foreground"
                    />
                </dl>

                <div className="grid gap-6 lg:grid-cols-3">
                    <section
                        className="space-y-3 rounded-2xl border bg-card p-5 lg:col-span-2"
                        aria-labelledby="week"
                    >
                        <h2 id="week" className="text-lg font-semibold">
                            Minutes studied this week
                        </h2>
                        <WeeklyChart week={week} />
                    </section>

                    <section
                        className="flex flex-col rounded-2xl border bg-card p-5"
                        aria-labelledby="goals"
                    >
                        <div className="flex items-center justify-between gap-2">
                            <h2 id="goals" className="text-lg font-semibold">
                                Today&apos;s goals
                            </h2>
                            <Button variant="ghost" size="sm" asChild>
                                <Link href="/settings/learning">Change</Link>
                            </Button>
                        </div>
                        <div className="my-auto grid grid-cols-2 gap-2 py-4">
                            <GoalRing
                                label="Minutes"
                                value={today.minutes}
                                goal={today.goal_minutes}
                            />
                            <GoalRing
                                label="Sentences"
                                value={today.sentences}
                                goal={today.goal_sentences}
                            />
                        </div>
                    </section>
                </div>

                <div className="grid gap-6 lg:grid-cols-3">
                    <div className="space-y-6 lg:col-span-2">
                        <section className="space-y-3" aria-labelledby="recent">
                            <h2 id="recent" className="text-lg font-semibold">
                                Recent practice
                            </h2>
                            {recent.length === 0 ? (
                                <p className="rounded-2xl border border-dashed p-6 text-sm text-muted-foreground">
                                    Nothing yet.{' '}
                                    <Link
                                        href={CatalogController.index()}
                                        className="underline"
                                    >
                                        Pick a course
                                    </Link>{' '}
                                    and start your first lesson.
                                </p>
                            ) : (
                                <ul className="divide-y overflow-hidden rounded-2xl border bg-card">
                                    {recent.map((session) => (
                                        <li key={session.id}>
                                            <Link
                                                href={PracticeController.summary(
                                                    session.id,
                                                )}
                                                className="flex items-center gap-3 p-4 hover:bg-accent/50"
                                            >
                                                <div className="min-w-0 flex-1">
                                                    <p className="truncate font-medium">
                                                        {session.title}
                                                    </p>
                                                    <p className="text-sm text-muted-foreground">
                                                        <span className="capitalize">
                                                            {session.mode}
                                                        </span>{' '}
                                                        ·{' '}
                                                        {formatDate(
                                                            session.completed_at,
                                                        )}
                                                    </p>
                                                </div>
                                                <span className="text-sm text-muted-foreground tabular-nums">
                                                    {session.correct}/
                                                    {session.total}
                                                </span>
                                                <span
                                                    className={cn(
                                                        'rounded-full px-2.5 py-1 text-sm font-medium tabular-nums',
                                                        percentTone(
                                                            session.accuracy,
                                                        ),
                                                    )}
                                                >
                                                    {formatPercent(
                                                        session.accuracy,
                                                    )}
                                                </span>
                                                <ChevronRight
                                                    className="size-4 text-muted-foreground"
                                                    aria-hidden
                                                />
                                            </Link>
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </section>

                        <section className="space-y-3" aria-labelledby="badges">
                            <h2 id="badges" className="text-lg font-semibold">
                                Badges
                            </h2>
                            {badges.length === 0 ? (
                                <p className="rounded-2xl border border-dashed p-5 text-sm text-muted-foreground">
                                    Finish a practice session to earn your first
                                    badge.
                                </p>
                            ) : (
                                <ul className="flex flex-wrap gap-2">
                                    {badges.map((badge) => (
                                        <li key={badge.name}>
                                            <span
                                                className="inline-flex items-center gap-1.5 rounded-full border bg-card px-3 py-1.5 text-sm"
                                                title={
                                                    badge.description ??
                                                    undefined
                                                }
                                            >
                                                <Trophy
                                                    className="size-4 text-star"
                                                    aria-hidden
                                                />{' '}
                                                {badge.name}
                                            </span>
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </section>
                    </div>

                    <section
                        className="h-fit space-y-4 rounded-2xl border bg-card p-5"
                        aria-labelledby="totals"
                    >
                        <h2 id="totals" className="text-lg font-semibold">
                            Your totals
                        </h2>
                        <dl className="divide-y">
                            {totals.map((total) => (
                                <div
                                    key={total.label}
                                    className="flex items-center justify-between gap-3 py-3 first:pt-0 last:pb-0"
                                >
                                    <dt className="flex items-center gap-2 text-sm text-muted-foreground">
                                        <total.icon
                                            className="size-4"
                                            aria-hidden
                                        />{' '}
                                        {total.label}
                                    </dt>
                                    <dd className="stat-figure text-lg font-semibold">
                                        {total.value}
                                    </dd>
                                </div>
                            ))}
                        </dl>
                    </section>
                </div>
            </div>
        </>
    );
}

Dashboard.layout = {
    breadcrumbs: [{ title: 'Dashboard', href: dashboard() }],
};
