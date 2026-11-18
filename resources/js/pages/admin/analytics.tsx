import { Head, Link } from '@inertiajs/react';
import {
    Bar,
    BarChart,
    CartesianGrid,
    Line,
    LineChart,
    ResponsiveContainer,
    Tooltip,
    XAxis,
    YAxis,
} from 'recharts';
import AnalyticsController from '@/actions/App/Modules/Admin/Http/Controllers/AnalyticsController';
import AudioLibraryController from '@/actions/App/Modules/Admin/Http/Controllers/AudioLibraryController';
import DashboardController from '@/actions/App/Modules/Admin/Http/Controllers/DashboardController';
import { PageHeader } from '@/components/admin/page-header';
import { formatPercent } from '@/lib/learn';

type Props = {
    totals: {
        learners: number;
        new_this_week: number;
        active_7: number;
        active_30: number;
        sessions: number;
        attempts: number;
        study_hours: number;
        average_accuracy: number | null;
        xp: number;
    };
    days: { date: string; label: string; users: number; attempts: number }[];
    courses: {
        title: string;
        started: number;
        completed: number;
        rate: number;
    }[];
    hardest: {
        text: string;
        lesson: string | null;
        missed: number;
        learners: number;
    }[];
    content: { missing_audio: number; draft_courses: number };
};

const tooltipStyle = {
    background: 'var(--popover)',
    border: '1px solid var(--border)',
    borderRadius: 8,
    color: 'var(--popover-foreground)',
};

export default function AdminAnalytics({
    totals,
    days,
    courses,
    hardest,
    content,
}: Props) {
    const tiles = [
        ['Learners', totals.learners],
        ['New this week', totals.new_this_week],
        ['Active, last 7 days', totals.active_7],
        ['Active, last 30 days', totals.active_30],
        ['Sessions finished', totals.sessions],
        ['Sentences typed', totals.attempts],
        ['Hours studied', totals.study_hours],
        ['Average text match', formatPercent(totals.average_accuracy)],
    ];

    return (
        <>
            <Head title="Analytics" />
            <div className="flex flex-col gap-6 p-4">
                <PageHeader
                    title="Learning analytics"
                    description="All learners together. No names, emails or answers are shown."
                />

                <dl className="grid grid-cols-2 gap-3 md:grid-cols-4">
                    {tiles.map(([label, value]) => (
                        <div
                            key={label}
                            className="rounded-xl border bg-card p-4"
                        >
                            <dt className="text-sm text-muted-foreground">
                                {label}
                            </dt>
                            <dd className="mt-1 text-2xl font-semibold tabular-nums">
                                {value}
                            </dd>
                        </div>
                    ))}
                </dl>

                <div className="grid gap-6 lg:grid-cols-2">
                    <section
                        className="space-y-3 rounded-xl border bg-card p-4"
                        aria-labelledby="active"
                    >
                        <h2 id="active" className="font-semibold">
                            Learners practising per day
                        </h2>
                        <div className="h-60" aria-hidden>
                            <ResponsiveContainer width="100%" height="100%">
                                <LineChart
                                    accessibilityLayer={false}
                                    data={days}
                                    margin={{
                                        top: 8,
                                        right: 8,
                                        bottom: 0,
                                        left: -16,
                                    }}
                                >
                                    <CartesianGrid
                                        vertical={false}
                                        stroke="var(--border)"
                                    />
                                    <XAxis
                                        dataKey="label"
                                        tickLine={false}
                                        axisLine={false}
                                        interval={6}
                                    />
                                    <YAxis
                                        allowDecimals={false}
                                        tickLine={false}
                                        axisLine={false}
                                    />
                                    <Tooltip
                                        contentStyle={tooltipStyle}
                                        formatter={(value) => [
                                            String(value),
                                            'Learners',
                                        ]}
                                    />
                                    <Line
                                        type="monotone"
                                        dataKey="users"
                                        stroke="var(--primary)"
                                        strokeWidth={2}
                                        dot={false}
                                    />
                                </LineChart>
                            </ResponsiveContainer>
                        </div>
                    </section>
                    <section
                        className="space-y-3 rounded-xl border bg-card p-4"
                        aria-labelledby="typed"
                    >
                        <h2 id="typed" className="font-semibold">
                            Sentences typed per day
                        </h2>
                        <div className="h-60" aria-hidden>
                            <ResponsiveContainer width="100%" height="100%">
                                <BarChart
                                    accessibilityLayer={false}
                                    data={days}
                                    margin={{
                                        top: 8,
                                        right: 8,
                                        bottom: 0,
                                        left: -16,
                                    }}
                                >
                                    <CartesianGrid
                                        vertical={false}
                                        stroke="var(--border)"
                                    />
                                    <XAxis
                                        dataKey="label"
                                        tickLine={false}
                                        axisLine={false}
                                        interval={6}
                                    />
                                    <YAxis
                                        allowDecimals={false}
                                        tickLine={false}
                                        axisLine={false}
                                    />
                                    <Tooltip
                                        cursor={{ fill: 'var(--accent)' }}
                                        contentStyle={tooltipStyle}
                                        formatter={(value) => [
                                            String(value),
                                            'Sentences',
                                        ]}
                                    />
                                    <Bar
                                        dataKey="attempts"
                                        fill="var(--primary)"
                                        radius={[4, 4, 0, 0]}
                                    />
                                </BarChart>
                            </ResponsiveContainer>
                        </div>
                    </section>
                </div>

                <div className="grid gap-6 lg:grid-cols-2">
                    <section className="space-y-3" aria-labelledby="courses">
                        <h2 id="courses" className="font-semibold">
                            Courses by learners who started them
                        </h2>
                        {courses.length === 0 ? (
                            <p className="rounded-xl border border-dashed p-6 text-sm text-muted-foreground">
                                No one has started a course yet.
                            </p>
                        ) : (
                            <ul className="divide-y rounded-xl border bg-card">
                                {courses.map((course) => (
                                    <li
                                        key={course.title}
                                        className="flex items-center justify-between gap-3 p-3 text-sm"
                                    >
                                        <span className="line-clamp-1 font-medium">
                                            {course.title}
                                        </span>
                                        <span className="shrink-0 text-muted-foreground">
                                            {course.started} started ·{' '}
                                            {course.completed} finished (
                                            {course.rate}%)
                                        </span>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </section>

                    <section className="space-y-3" aria-labelledby="hardest">
                        <h2 id="hardest" className="font-semibold">
                            Hardest sentences
                        </h2>
                        {hardest.length === 0 ? (
                            <p className="rounded-xl border border-dashed p-6 text-sm text-muted-foreground">
                                No mistakes recorded yet.
                            </p>
                        ) : (
                            <ul className="divide-y rounded-xl border bg-card">
                                {hardest.map((item) => (
                                    <li
                                        key={item.text}
                                        className="space-y-0.5 p-3 text-sm"
                                    >
                                        <p className="line-clamp-1 font-medium">
                                            {item.text}
                                        </p>
                                        <p className="text-xs text-muted-foreground">
                                            {item.lesson ?? '-'} · missed{' '}
                                            {item.missed} times by{' '}
                                            {item.learners} learner
                                            {item.learners === 1 ? '' : 's'}
                                        </p>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </section>
                </div>

                <section className="space-y-2" aria-labelledby="health">
                    <h2 id="health" className="font-semibold">
                        Content to fix
                    </h2>
                    <ul className="divide-y rounded-xl border bg-card text-sm">
                        <li className="flex items-center justify-between p-3">
                            <span>
                                {content.missing_audio} sentences, words or
                                phrases have no audio
                            </span>
                            <Link
                                href={AudioLibraryController.index({
                                    query: { view: 'missing' },
                                })}
                                className="underline"
                            >
                                See which
                            </Link>
                        </li>
                        <li className="p-3">
                            {content.draft_courses} courses are still drafts
                        </li>
                    </ul>
                </section>
            </div>
        </>
    );
}

AdminAnalytics.layout = {
    breadcrumbs: [
        { title: 'Content admin', href: DashboardController.index() },
        { title: 'Analytics', href: AnalyticsController() },
    ],
};
