import { Head, Link, router } from '@inertiajs/react';
import { CircleCheck, Hourglass, Play } from 'lucide-react';
import { EmptyState } from '@/components/empty-state';
import MistakeController from '@/actions/App/Modules/Dictation/Http/Controllers/MistakeController';
import { PageHeader } from '@/components/admin/page-header';
import { Pagination } from '@/components/admin/pagination';
import { DiffLegend, DiffView } from '@/components/diff-view';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import { formatDate } from '@/lib/learn';
import { dashboard } from '@/routes';
import type { DiffToken, Paginated } from '@/types';

import { useT } from '@/lib/i18n';
type Mistake = {
    id: string;
    expected: string;
    bengali: string | null;
    typed: string | null;
    tokens: DiffToken[];
    accuracy: number | null;
    times_missed: number;
    last_missed_at: string;
    resolved: boolean;
};

export default function MistakesIndex({
    items,
    show,
    counts,
}: {
    items: Paginated<Mistake>;
    show: 'open' | 'resolved';
    counts: { open: number; resolved: number };
}) {
    const t = useT();
    const tabs = [
        {
            key: 'open',
            label: t('Open ({count})', { count: counts.open }),
            href: MistakeController.index(),
        },
        {
            key: 'resolved',
            label: t('Resolved ({count})', { count: counts.resolved }),
            href: MistakeController.index({ query: { show: 'resolved' } }),
        },
    ] as const;

    return (
        <>
            <Head title={t('Mistakes notebook')} />
            <div className="mx-auto flex w-full max-w-4xl animate-rise flex-col gap-6 p-4 md:p-6">
                <PageHeader
                    title={t('Mistakes notebook')}
                    description={t(
                        'Every sentence you got wrong is saved here. Practise it again until you get it right.',
                    )}
                    actions={
                        counts.open > 0 && (
                            <Button
                                onClick={() =>
                                    router.post(
                                        MistakeController.practiceAll.url(),
                                    )
                                }
                            >
                                <Play /> {t('Practise open mistakes')}
                            </Button>
                        )
                    }
                />

                <nav
                    aria-label={t('Mistake views')}
                    className="flex gap-1 border-b"
                >
                    {tabs.map((tab) => (
                        <Link
                            key={tab.key}
                            href={tab.href}
                            aria-current={show === tab.key ? 'page' : undefined}
                            className={cn(
                                '-mb-px border-b-2 px-3 py-2 text-sm',
                                show === tab.key
                                    ? 'border-primary font-medium'
                                    : 'border-transparent text-muted-foreground hover:text-foreground',
                            )}
                        >
                            {tab.label}
                        </Link>
                    ))}
                </nav>

                {items.data.length === 0 ? (
                    show === 'open' ? (
                        <EmptyState
                            icon={CircleCheck}
                            title={t('No open mistakes')}
                        >
                            {t(
                                'Nice work. A sentence you get wrong waits here until you type it right.',
                            )}
                        </EmptyState>
                    ) : (
                        <EmptyState
                            icon={Hourglass}
                            title={t('Nothing resolved yet')}
                        >
                            {t(
                                'Practise a mistake again and get it right to see it here.',
                            )}
                        </EmptyState>
                    )
                ) : (
                    <>
                        <DiffLegend />
                        <ul className="space-y-3">
                            {items.data.map((item) => (
                                <li
                                    key={item.id}
                                    className="space-y-3 rounded-xl border bg-card p-4"
                                >
                                    <div className="flex flex-wrap items-center justify-between gap-2 text-xs text-muted-foreground">
                                        <span>
                                            {t(
                                                'Missed {times} · last on {date}',
                                                {
                                                    times: t.n(
                                                        item.times_missed,
                                                        'time',
                                                    ),
                                                    date: formatDate(
                                                        item.last_missed_at,
                                                    ),
                                                },
                                            )}
                                            {item.accuracy !== null &&
                                                ` · ${t('{percent}% match', { percent: item.accuracy })}`}
                                        </span>
                                        {item.resolved && (
                                            <Badge
                                                variant="outline"
                                                className="border-success/30 bg-success/10 text-success"
                                            >
                                                <CircleCheck /> {t('Resolved')}
                                            </Badge>
                                        )}
                                    </div>

                                    {item.typed !== null && (
                                        <div>
                                            <p className="text-xs text-muted-foreground">
                                                {t('Your last answer')}
                                            </p>
                                            <DiffView tokens={item.tokens} />
                                        </div>
                                    )}
                                    <div>
                                        <p className="text-xs text-muted-foreground">
                                            {t('Correct text')}
                                        </p>
                                        <p className="font-medium">
                                            {item.expected}
                                        </p>
                                        {item.bengali && (
                                            <p
                                                lang="bn"
                                                className="text-sm text-muted-foreground"
                                            >
                                                {item.bengali}
                                            </p>
                                        )}
                                    </div>

                                    <div className="flex flex-wrap gap-2">
                                        <Button
                                            size="sm"
                                            onClick={() =>
                                                router.post(
                                                    MistakeController.practiceAgain.url(
                                                        item.id,
                                                    ),
                                                )
                                            }
                                        >
                                            <Play /> {t('Practise again')}
                                        </Button>
                                        {!item.resolved && (
                                            <Button
                                                size="sm"
                                                variant="ghost"
                                                onClick={() =>
                                                    router.patch(
                                                        MistakeController.dismiss.url(
                                                            item.id,
                                                        ),
                                                        {},
                                                        {
                                                            preserveScroll: true,
                                                        },
                                                    )
                                                }
                                            >
                                                {t('Mark as resolved')}
                                            </Button>
                                        )}
                                    </div>
                                </li>
                            ))}
                        </ul>
                    </>
                )}

                <Pagination links={items.links} total={items.total} />
            </div>
        </>
    );
}

MistakesIndex.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard() },
        { title: 'Mistakes', href: MistakeController.index() },
    ],
};
