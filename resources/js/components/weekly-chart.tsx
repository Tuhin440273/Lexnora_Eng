import {
    Bar,
    BarChart,
    CartesianGrid,
    Cell,
    ResponsiveContainer,
    Tooltip,
    XAxis,
} from 'recharts';

export type WeekDay = {
    date: string;
    label: string;
    minutes: number;
    sentences: number;
    xp: number;
};

/**
 * Minutes studied per day, today highlighted. The data is also listed as text
 * for screen readers.
 */
export function WeeklyChart({ week }: { week: WeekDay[] }) {
    const empty = week.every((day) => day.minutes === 0);
    const today = week.length - 1;

    return (
        <div>
            <div className="relative h-56 w-full" aria-hidden>
                <ResponsiveContainer width="100%" height="100%">
                    <BarChart
                        data={week}
                        margin={{ top: 8, right: 8, bottom: 0, left: 8 }}
                    >
                        <CartesianGrid
                            vertical={false}
                            strokeDasharray="3 3"
                            stroke="var(--border)"
                        />
                        <XAxis
                            dataKey="label"
                            tickLine={false}
                            axisLine={false}
                        />
                        <Tooltip
                            cursor={{ fill: 'var(--accent)' }}
                            contentStyle={{
                                background: 'var(--popover)',
                                border: '1px solid var(--border)',
                                borderRadius: 12,
                                color: 'var(--popover-foreground)',
                            }}
                            formatter={(value) => [
                                `${String(value)} min`,
                                'Studied',
                            ]}
                        />
                        <Bar
                            dataKey="minutes"
                            radius={[8, 8, 0, 0]}
                            maxBarSize={36}
                        >
                            {week.map((day, index) => (
                                <Cell
                                    key={day.date}
                                    fill={
                                        index === today
                                            ? 'var(--primary)'
                                            : 'color-mix(in oklab, var(--primary) 45%, transparent)'
                                    }
                                />
                            ))}
                        </Bar>
                    </BarChart>
                </ResponsiveContainer>
                {empty && (
                    <p className="absolute inset-x-0 top-1/3 text-center text-sm text-muted-foreground">
                        No study time yet this week. One short session fills the
                        first bar.
                    </p>
                )}
            </div>
            <ul className="sr-only">
                {week.map((day) => (
                    <li key={day.date}>
                        {day.label}: {day.minutes} minutes, {day.sentences}{' '}
                        sentences
                    </li>
                ))}
            </ul>
        </div>
    );
}
