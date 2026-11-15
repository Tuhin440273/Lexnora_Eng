import { Head, Link, router } from '@inertiajs/react';
import {
    BookCheck,
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
import { PageHeader } from '@/components/admin/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { WeeklyChart } from '@/components/weekly-chart';
import type { WeekDay } from '@/components/weekly-chart';
import { formatDate, formatPercent, formatStudyTime } from '@/lib/learn';
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

function Goal({
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

    return (
        <div className="space-y-1">
            <div className="flex justify-between text-sm">
                <span>{label}</span>
                <span className="text-muted-foreground tabular-nums">
                    {value} / {goal}
                </span>
            </div>
            <div
                role="progressbar"
                aria-label={label}
                aria-valuemin={0}
                aria-valuemax={100}
                aria-valuenow={percent}
                className="h-2 overflow-hidden rounded-full bg-muted"
            >
                <div
                    className="h-full rounded-full bg-primary"
                    style={{ width: `${percent}%` }}
                />
            </div>
        </div>
    );
}

export default function Dashboard({
    stats,
    today,
    week,
    recent,
    continue: resume,
    badges,
}: Props) {
    const tiles: { icon: LucideIcon; label: string; value: string }[] = [
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
        { icon: Flame, label: 'Day streak', value: String(stats.streak) },
        {
            icon: BookCheck,
            label: 'Words learned',
            value: String(stats.vocabulary_learned),
        },
        {
            icon: RotateCw,
            label: 'Reviews due',
            value: String(stats.review_due),
        },
        { icon: Sparkles, label: 'Total XP', value: String(stats.xp) },
    ];

    return (
        <>
            <Head title="Dashboard" />
            <div className="flex flex-col gap-6 p-4">
                <PageHeader
                    title="Dashboard"
                    description="Your learning at a glance."
                    actions={
                        <>
                            {stats.review_due > 0 && (
                                <Button variant="outline" asChild>
                                    <Link href={ReviewController.index()}>
                                        <RotateCw /> {stats.review_due} to
                                        review
                                    </Link>
                                </Button>
                            )}
                            <Button
                                variant={
                                    today.checked_in ? 'outline' : 'default'
                                }
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
                        </>
                    }
                />

                {resume && (
                    <section className="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-primary/30 bg-accent p-4">
                        <div>
                            <p className="text-sm text-muted-foreground">
                                Continue where you stopped
                            </p>
                            <p className="font-semibold">
                                {resume.title} · {resume.done} of {resume.total}{' '}
                                done
                            </p>
                        </div>
                        <Button asChild>
                            <Link href={PracticeController.show(resume.id)}>
                                <Play /> Continue
                            </Link>
                        </Button>
                    </section>
                )}

                <dl className="grid grid-cols-2 gap-3 md:grid-cols-4">
                    {tiles.map((tile) => (
                        <div
                            key={tile.label}
                            className="rounded-xl border bg-card p-4"
                        >
                            <dt className="flex items-center gap-1.5 text-sm text-muted-foreground">
                                <tile.icon className="size-4" aria-hidden />{' '}
                                {tile.label}
                            </dt>
                            <dd className="mt-1 text-2xl font-semibold tabular-nums">
                                {tile.value}
                            </dd>
                        </div>
                    ))}
                </dl>

                <div className="grid gap-6 lg:grid-cols-3">
                    <section
                        className="space-y-3 rounded-xl border bg-card p-4 lg:col-span-2"
                        aria-labelledby="week"
                    >
                        <h2 id="week" className="font-semibold">
                            Minutes studied this week
                        </h2>
                        <WeeklyChart week={week} />
                    </section>

                    <section
                        className="space-y-4 rounded-xl border bg-card p-4"
                        aria-labelledby="goals"
                    >
                        <h2 id="goals" className="font-semibold">
                            Today&apos;s goals
                        </h2>
                        <Goal
                            label="Minutes"
                            value={today.minutes}
                            goal={today.goal_minutes}
                        />
                        <Goal
                            label="Sentences"
                            value={today.sentences}
                            goal={today.goal_sentences}
                        />
                        <Button variant="ghost" size="sm" asChild>
                            <Link href="/settings/learning">Change goals</Link>
                        </Button>
                    </section>
                </div>

                <div className="grid gap-6 lg:grid-cols-2">
                    <section className="space-y-3" aria-labelledby="recent">
                        <h2 id="recent" className="font-semibold">
                            Recent practice
                        </h2>
                        {recent.length === 0 ? (
                            <p className="rounded-xl border border-dashed p-6 text-sm text-muted-foreground">
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
                            <ul className="divide-y rounded-xl border bg-card">
                                {recent.map((session) => (
                                    <li key={session.id}>
                                        <Link
                                            href={PracticeController.summary(
                                                session.id,
                                            )}
                                            className="flex flex-wrap items-center justify-between gap-2 p-3 text-sm hover:bg-accent/50"
                                        >
                                            <span className="font-medium">
                                                {session.title}
                                            </span>
                                            <span className="text-muted-foreground capitalize">
                                                {session.mode}
                                            </span>
                                            <span>
                                                {session.correct}/
                                                {session.total} ·{' '}
                                                {formatPercent(
                                                    session.accuracy,
                                                )}
                                            </span>
                                            <span className="text-muted-foreground">
                                                {formatDate(
                                                    session.completed_at,
                                                )}
                                            </span>
                                        </Link>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </section>

                    <section className="space-y-3" aria-labelledby="badges">
                        <h2 id="badges" className="font-semibold">
                            Badges
                        </h2>
                        {badges.length === 0 ? (
                            <p className="rounded-xl border border-dashed p-6 text-sm text-muted-foreground">
                                Finish a practice session to earn your first
                                badge.
                            </p>
                        ) : (
                            <ul className="flex flex-wrap gap-2">
                                {badges.map((badge) => (
                                    <li key={badge.name}>
                                        <Badge
                                            variant="secondary"
                                            className="gap-1.5 px-3 py-1.5 text-sm"
                                            title={
                                                badge.description ?? undefined
                                            }
                                        >
                                            <Trophy
                                                className="size-4"
                                                aria-hidden
                                            />{' '}
                                            {badge.name}
                                        </Badge>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </section>
                </div>
            </div>
        </>
    );
}

Dashboard.layout = {
    breadcrumbs: [{ title: 'Dashboard', href: dashboard() }],
};
