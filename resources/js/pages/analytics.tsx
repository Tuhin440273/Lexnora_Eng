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
import MistakeController from '@/actions/App/Modules/Dictation/Http/Controllers/MistakeController';
import { PageHeader } from '@/components/admin/page-header';
import { analytics, dashboard } from '@/routes';

type Day = {
    date: string;
    label: string;
    minutes: number;
    sentences: number;
    accuracy: number | null;
};

const tooltipStyle = {
    background: 'var(--popover)',
    border: '1px solid var(--border)',
    borderRadius: 8,
    color: 'var(--popover-foreground)',
};

export default function Analytics({
    days,
    by_mode,
    hardest,
}: {
    days: Day[];
    by_mode: { mode: string; sessions: number; accuracy: number }[];
    hardest: { text: string; times_missed: number }[];
}) {
    const hasData = days.some((day) => day.sentences > 0);

    return (
        <>
            <Head title="Analytics" />
            <div className="flex flex-col gap-6 p-4">
                <PageHeader
                    title="Your progress"
                    description="The last 30 days of practice."
                />

                {!hasData && (
                    <p className="rounded-xl border border-dashed p-8 text-center text-sm text-muted-foreground">
                        Charts appear after your first practice session.
                    </p>
                )}

                <div className="grid gap-6 lg:grid-cols-2">
                    <section
                        className="space-y-3 rounded-xl border bg-card p-4"
                        aria-labelledby="accuracy"
                    >
                        <h2 id="accuracy" className="font-semibold">
                            Text match per day (%)
                        </h2>
                        <div className="h-60" aria-hidden>
                            <ResponsiveContainer width="100%" height="100%">
                                <LineChart
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
                                        domain={[0, 100]}
                                        tickLine={false}
                                        axisLine={false}
                                    />
                                    <Tooltip
                                        contentStyle={tooltipStyle}
                                        formatter={(value) => [
                                            `${String(value)}%`,
                                            'Text match',
                                        ]}
                                    />
                                    <Line
                                        type="monotone"
                                        dataKey="accuracy"
                                        stroke="var(--primary)"
                                        strokeWidth={2}
                                        dot={false}
                                        connectNulls
                                    />
                                </LineChart>
                            </ResponsiveContainer>
                        </div>
                    </section>

                    <section
                        className="space-y-3 rounded-xl border bg-card p-4"
                        aria-labelledby="minutes"
                    >
                        <h2 id="minutes" className="font-semibold">
                            Minutes studied per day
                        </h2>
                        <div className="h-60" aria-hidden>
                            <ResponsiveContainer width="100%" height="100%">
                                <BarChart
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
                                            `${String(value)} min`,
                                            'Studied',
                                        ]}
                                    />
                                    <Bar
                                        dataKey="minutes"
                                        fill="var(--primary)"
                                        radius={[4, 4, 0, 0]}
                                    />
                                </BarChart>
                            </ResponsiveContainer>
                        </div>
                    </section>
                </div>

                <div className="grid gap-6 lg:grid-cols-2">
                    <section className="space-y-3" aria-labelledby="modes">
                        <h2 id="modes" className="font-semibold">
                            By practice mode
                        </h2>
                        <ul className="divide-y rounded-xl border bg-card">
                            {by_mode.map((mode) => (
                                <li
                                    key={mode.mode}
                                    className="flex items-center justify-between p-3 text-sm"
                                >
                                    <span className="font-medium">
                                        {mode.mode}
                                    </span>
                                    <span className="text-muted-foreground">
                                        {mode.sessions} sessions
                                    </span>
                                    <span>
                                        {mode.sessions > 0
                                            ? `${mode.accuracy}% average`
                                            : '-'}
                                    </span>
                                </li>
                            ))}
                        </ul>
                    </section>

                    <section className="space-y-3" aria-labelledby="hardest">
                        <h2 id="hardest" className="font-semibold">
                            Sentences you miss most
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
                                        className="flex items-center justify-between gap-3 p-3 text-sm"
                                    >
                                        <span className="line-clamp-1">
                                            {item.text}
                                        </span>
                                        <span className="shrink-0 text-muted-foreground">
                                            {item.times_missed}×
                                        </span>
                                    </li>
                                ))}
                            </ul>
                        )}
                        {hardest.length > 0 && (
                            <Link
                                href={MistakeController.index()}
                                className="text-sm underline"
                            >
                                Open the mistakes notebook
                            </Link>
                        )}
                    </section>
                </div>
            </div>
        </>
    );
}

Analytics.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard() },
        { title: 'Progress', href: analytics() },
    ],
};
