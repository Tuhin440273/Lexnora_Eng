import {
    Bar,
    BarChart,
    CartesianGrid,
    ResponsiveContainer,
    Tooltip,
    XAxis,
    YAxis,
} from 'recharts';

export type WeekDay = {
    date: string;
    label: string;
    minutes: number;
    sentences: number;
    xp: number;
};

/**
 * Minutes studied per day. The data is also listed as text for screen readers.
 */
export function WeeklyChart({ week }: { week: WeekDay[] }) {
    return (
        <div>
            <div className="h-56 w-full" aria-hidden>
                <ResponsiveContainer width="100%" height="100%">
                    <BarChart
                        data={week}
                        margin={{ top: 8, right: 8, bottom: 0, left: -16 }}
                    >
                        <CartesianGrid
                            vertical={false}
                            stroke="var(--border)"
                        />
                        <XAxis
                            dataKey="label"
                            tickLine={false}
                            axisLine={false}
                        />
                        <YAxis
                            allowDecimals={false}
                            tickLine={false}
                            axisLine={false}
                        />
                        <Tooltip
                            cursor={{ fill: 'var(--accent)' }}
                            contentStyle={{
                                background: 'var(--popover)',
                                border: '1px solid var(--border)',
                                borderRadius: 8,
                                color: 'var(--popover-foreground)',
                            }}
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
