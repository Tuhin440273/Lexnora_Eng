import { Head, Link, router } from '@inertiajs/react';
import { Clock, RotateCcw, Sparkles, Target } from 'lucide-react';
import CatalogController from '@/actions/App/Modules/Courses/Http/Controllers/CatalogController';
import MistakeController from '@/actions/App/Modules/Dictation/Http/Controllers/MistakeController';
import PracticeController from '@/actions/App/Modules/Dictation/Http/Controllers/PracticeController';
import { PageHeader } from '@/components/admin/page-header';
import { DiffLegend, DiffView } from '@/components/diff-view';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { formatPercent, formatStudyTime } from '@/lib/learn';
import { dashboard } from '@/routes';
import type { DiffToken } from '@/types';

type Props = {
    session: {
        id: string;
        mode: 'easy' | 'medium' | 'hard';
        kind: string;
        title: string;
        lesson_id: string | null;
        total: number;
        correct: number;
        average_accuracy: number;
        duration_seconds: number;
        xp: number;
    };
    attempts: {
        id: string;
        expected: string;
        typed: string;
        accuracy: number;
        is_correct: boolean;
        tokens: DiffToken[];
        bengali: string | null;
        hints_used: number;
    }[];
};

export default function PracticeSummary({ session, attempts }: Props) {
    const wrong = attempts.filter((attempt) => !attempt.is_correct).length;

    const stats = [
        {
            icon: Target,
            label: 'Correct',
            value: `${session.correct} / ${session.total}`,
        },
        {
            icon: Sparkles,
            label: 'Average text match',
            value: formatPercent(session.average_accuracy),
        },
        {
            icon: Clock,
            label: 'Study time',
            value: formatStudyTime(session.duration_seconds),
        },
        { icon: Sparkles, label: 'XP earned', value: `+${session.xp}` },
    ];

    return (
        <>
            <Head title="Session summary" />
            <div className="mx-auto flex w-full max-w-3xl flex-col gap-6 p-4">
                <PageHeader
                    title="Session finished"
                    description={`${session.title} · ${session.kind === 'lesson' ? `${session.mode} mode` : session.kind}`}
                    actions={
                        session.lesson_id ? (
                            <Button
                                onClick={() =>
                                    router.post(
                                        PracticeController.start.url(
                                            session.lesson_id as string,
                                        ),
                                        { mode: session.mode },
                                    )
                                }
                            >
                                <RotateCcw /> Practise again
                            </Button>
                        ) : undefined
                    }
                />

                <dl className="grid grid-cols-2 gap-3 md:grid-cols-4">
                    {stats.map((stat) => (
                        <div
                            key={stat.label}
                            className="rounded-xl border bg-card p-4"
                        >
                            <dt className="flex items-center gap-1.5 text-sm text-muted-foreground">
                                <stat.icon className="size-4" /> {stat.label}
                            </dt>
                            <dd className="mt-1 text-2xl font-semibold tabular-nums">
                                {stat.value}
                            </dd>
                        </div>
                    ))}
                </dl>

                <section className="space-y-3" aria-labelledby="answers">
                    <div className="flex items-center justify-between">
                        <h2 id="answers" className="text-lg font-semibold">
                            Your answers
                        </h2>
                        {wrong > 0 && (
                            <Button variant="outline" size="sm" asChild>
                                <Link href={MistakeController.index()}>
                                    {wrong} to review in your mistakes
                                </Link>
                            </Button>
                        )}
                    </div>
                    <DiffLegend />
                    <ul className="space-y-3">
                        {attempts.map((attempt) => (
                            <li
                                key={attempt.id}
                                className="space-y-2 rounded-xl border bg-card p-4"
                            >
                                <div className="flex items-center justify-between gap-2">
                                    <Badge
                                        variant="outline"
                                        className={
                                            attempt.is_correct
                                                ? 'border-success/30 bg-success/10 text-success'
                                                : 'border-destructive/30 bg-destructive/10 text-destructive'
                                        }
                                    >
                                        {attempt.is_correct
                                            ? 'Correct'
                                            : 'Not quite'}{' '}
                                        · {attempt.accuracy}%
                                    </Badge>
                                    {attempt.hints_used > 0 && (
                                        <span className="text-xs text-muted-foreground">
                                            {attempt.hints_used} hint
                                            {attempt.hints_used === 1
                                                ? ''
                                                : 's'}{' '}
                                            used
                                        </span>
                                    )}
                                </div>
                                <DiffView tokens={attempt.tokens} />
                                {attempt.bengali && (
                                    <p
                                        lang="bn"
                                        className="text-sm text-muted-foreground"
                                    >
                                        {attempt.bengali}
                                    </p>
                                )}
                            </li>
                        ))}
                    </ul>
                </section>

                <div className="flex flex-wrap gap-2">
                    {session.lesson_id && (
                        <Button variant="outline" asChild>
                            <Link
                                href={CatalogController.showLesson(
                                    session.lesson_id,
                                )}
                            >
                                Back to the lesson
                            </Link>
                        </Button>
                    )}
                    <Button variant="outline" asChild>
                        <Link href={dashboard()}>Dashboard</Link>
                    </Button>
                </div>
            </div>
        </>
    );
}

PracticeSummary.layout = {
    breadcrumbs: [{ title: 'Dashboard', href: dashboard() }],
};
