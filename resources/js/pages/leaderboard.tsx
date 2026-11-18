import { Head, Link } from '@inertiajs/react';
import { Crown } from 'lucide-react';
import { PageHeader } from '@/components/admin/page-header';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { formatStudyTime } from '@/lib/learn';
import { cn } from '@/lib/utils';
import { dashboard, leaderboard } from '@/routes';

type Props = {
    metric: 'xp' | 'study_time';
    period: 'daily' | 'weekly' | 'monthly' | 'all';
    entries: { rank: number; name: string; value: number; is_me: boolean }[];
    me: { rank: number | null; value: number };
};

const PERIODS = [
    { key: 'daily', label: 'Today' },
    { key: 'weekly', label: 'This week' },
    { key: 'monthly', label: 'This month' },
    { key: 'all', label: 'All time' },
] as const;

export default function Leaderboard({ metric, period, entries, me }: Props) {
    const show = (value: number) =>
        metric === 'xp' ? `${value} XP` : formatStudyTime(value);

    return (
        <>
            <Head title="Leaderboard" />
            <div className="mx-auto flex w-full max-w-3xl animate-rise flex-col gap-6 p-4 md:p-6">
                <PageHeader
                    title="Leaderboard"
                    description={`Ranked by ${metric === 'xp' ? 'XP earned' : 'time studied'}. Only first names or chosen display names are shown; you can hide yours in Settings > Learning.`}
                />

                <nav
                    aria-label="Period"
                    className="flex flex-wrap gap-1 border-b"
                >
                    {PERIODS.map((item) => (
                        <Link
                            key={item.key}
                            href={leaderboard({ query: { period: item.key } })}
                            aria-current={
                                period === item.key ? 'page' : undefined
                            }
                            className={cn(
                                '-mb-px border-b-2 px-3 py-2 text-sm',
                                period === item.key
                                    ? 'border-primary font-medium'
                                    : 'border-transparent text-muted-foreground hover:text-foreground',
                            )}
                        >
                            {item.label}
                        </Link>
                    ))}
                </nav>

                <p className="rounded-xl border bg-card p-4 text-sm">
                    {me.rank !== null
                        ? `You are number ${me.rank} with ${show(me.value)}.`
                        : 'You have no points in this period yet. Practise to join the ranking.'}
                </p>

                {entries.length === 0 ? (
                    <p className="rounded-xl border border-dashed p-8 text-center text-sm text-muted-foreground">
                        Nobody has earned points in this period yet.
                    </p>
                ) : (
                    <div className="rounded-xl border bg-card">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead className="w-16">Rank</TableHead>
                                    <TableHead>Learner</TableHead>
                                    <TableHead className="text-right">
                                        {metric === 'xp' ? 'XP' : 'Time'}
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {entries.map((entry) => (
                                    <TableRow
                                        key={entry.rank}
                                        className={cn(
                                            entry.is_me && 'bg-accent/60',
                                        )}
                                    >
                                        <TableCell className="font-semibold tabular-nums">
                                            {entry.rank === 1 ? (
                                                <span className="inline-flex items-center gap-1">
                                                    <Crown
                                                        className="size-4 text-warning"
                                                        aria-hidden
                                                    />{' '}
                                                    1
                                                </span>
                                            ) : (
                                                entry.rank
                                            )}
                                        </TableCell>
                                        <TableCell
                                            className={cn(
                                                entry.is_me && 'font-semibold',
                                            )}
                                        >
                                            {entry.name}
                                        </TableCell>
                                        <TableCell className="text-right tabular-nums">
                                            {show(entry.value)}
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </div>
                )}
            </div>
        </>
    );
}

Leaderboard.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard() },
        { title: 'Leaderboard', href: leaderboard() },
    ],
};
