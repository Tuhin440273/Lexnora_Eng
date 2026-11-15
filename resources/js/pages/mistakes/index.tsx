import { Head, Link, router } from '@inertiajs/react';
import { CircleCheck, Play } from 'lucide-react';
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
    const tabs = [
        {
            key: 'open',
            label: `Open (${counts.open})`,
            href: MistakeController.index(),
        },
        {
            key: 'resolved',
            label: `Resolved (${counts.resolved})`,
            href: MistakeController.index({ query: { show: 'resolved' } }),
        },
    ] as const;

    return (
        <>
            <Head title="Mistakes notebook" />
            <div className="flex max-w-4xl flex-col gap-6 p-4">
                <PageHeader
                    title="Mistakes notebook"
                    description="Every sentence you got wrong is saved here. Practise it again until you get it right."
                    actions={
                        counts.open > 0 && (
                            <Button
                                onClick={() =>
                                    router.post(
                                        MistakeController.practiceAll.url(),
                                    )
                                }
                            >
                                <Play /> Practise open mistakes
                            </Button>
                        )
                    }
                />

                <nav aria-label="Mistake views" className="flex gap-1 border-b">
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
                    <p className="rounded-xl border border-dashed p-8 text-center text-sm text-muted-foreground">
                        {show === 'open'
                            ? 'No open mistakes. Nice work.'
                            : 'Nothing resolved yet.'}
                    </p>
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
                                            Missed {item.times_missed} time
                                            {item.times_missed === 1
                                                ? ''
                                                : 's'}{' '}
                                            · last on{' '}
                                            {formatDate(item.last_missed_at)}
                                            {item.accuracy !== null &&
                                                ` · ${item.accuracy}% match`}
                                        </span>
                                        {item.resolved && (
                                            <Badge
                                                variant="outline"
                                                className="border-success/30 bg-success/10 text-success"
                                            >
                                                <CircleCheck /> Resolved
                                            </Badge>
                                        )}
                                    </div>

                                    {item.typed !== null && (
                                        <div>
                                            <p className="text-xs text-muted-foreground">
                                                Your last answer
                                            </p>
                                            <DiffView tokens={item.tokens} />
                                        </div>
                                    )}
                                    <div>
                                        <p className="text-xs text-muted-foreground">
                                            Correct text
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
                                            <Play /> Practise again
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
                                                Mark as resolved
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
