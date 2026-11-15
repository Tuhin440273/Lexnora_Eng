import { Head, Link, router } from '@inertiajs/react';
import { Play } from 'lucide-react';
import { useState } from 'react';
import ReviewController from '@/actions/App/Modules/Review/Http/Controllers/ReviewController';
import { PageHeader } from '@/components/admin/page-header';
import { Pagination } from '@/components/admin/pagination';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { cn } from '@/lib/utils';
import { formatDate } from '@/lib/learn';
import { dashboard } from '@/routes';
import type { Paginated } from '@/types';

type VocabCard = {
    schedule_id: string;
    term: string;
    type: string;
    context: string | null;
    meaning: string | null;
    meaning_bn: string | null;
};

type HistoryRow = {
    id: string;
    label: string;
    kind: string | null;
    is_successful: boolean;
    accuracy: number | null;
    previous_step: number;
    new_step: number;
    reviewed_at: string;
};

type Props = {
    tab: 'due' | 'history';
    due: { sentences: number; vocabulary: number; batch: number };
    vocabulary: VocabCard[];
    progress: { active: number; mastered: number; next_due_at: string | null };
    history: Paginated<HistoryRow> | null;
};

function VocabularyCard({ card }: { card: VocabCard }) {
    const [revealed, setRevealed] = useState(false);

    const rate = (result: 'remembered' | 'forgot') =>
        router.post(
            ReviewController.rateVocabulary.url(card.schedule_id),
            { result },
            { preserveScroll: true },
        );

    return (
        <li className="space-y-3 rounded-xl border bg-card p-4">
            <div className="flex items-center gap-2">
                <span className="text-xl font-semibold">{card.term}</span>
                <Badge variant="secondary">{card.type}</Badge>
            </div>

            {revealed ? (
                <div className="space-y-1 text-sm">
                    {card.meaning && <p>{card.meaning}</p>}
                    {card.meaning_bn && <p lang="bn">{card.meaning_bn}</p>}
                    {card.context && (
                        <p className="text-muted-foreground italic">
                            &ldquo;{card.context}&rdquo;
                        </p>
                    )}
                    {!card.meaning && !card.meaning_bn && !card.context && (
                        <p className="text-muted-foreground">
                            You have not added a meaning for this word.
                        </p>
                    )}
                </div>
            ) : (
                <Button
                    variant="outline"
                    size="sm"
                    onClick={() => setRevealed(true)}
                >
                    Show meaning
                </Button>
            )}

            <div className="flex gap-2">
                <Button size="sm" onClick={() => rate('remembered')}>
                    I remembered
                </Button>
                <Button
                    size="sm"
                    variant="outline"
                    onClick={() => rate('forgot')}
                >
                    I forgot
                </Button>
            </div>
        </li>
    );
}

export default function ReviewIndex({
    tab,
    due,
    vocabulary,
    progress,
    history,
}: Props) {
    const tabs = [
        { key: 'due', label: 'Due now', href: ReviewController.index() },
        {
            key: 'history',
            label: 'History',
            href: ReviewController.index({ query: { tab: 'history' } }),
        },
    ] as const;

    return (
        <>
            <Head title="Review center" />
            <div className="flex max-w-4xl flex-col gap-6 p-4">
                <PageHeader
                    title="Review center"
                    description="Come back to what you studied. Items return after longer and longer breaks, and start again if you get one wrong."
                />

                <nav aria-label="Review views" className="flex gap-1 border-b">
                    {tabs.map((item) => (
                        <Link
                            key={item.key}
                            href={item.href}
                            aria-current={tab === item.key ? 'page' : undefined}
                            className={cn(
                                '-mb-px border-b-2 px-3 py-2 text-sm',
                                tab === item.key
                                    ? 'border-primary font-medium'
                                    : 'border-transparent text-muted-foreground hover:text-foreground',
                            )}
                        >
                            {item.label}
                        </Link>
                    ))}
                </nav>

                {tab === 'due' && (
                    <>
                        <dl className="grid grid-cols-2 gap-3 md:grid-cols-4">
                            {[
                                ['Sentences due', due.sentences],
                                ['Words due', due.vocabulary],
                                ['In review', progress.active],
                                ['Mastered', progress.mastered],
                            ].map(([label, value]) => (
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

                        <section className="flex flex-wrap items-center justify-between gap-3 rounded-xl border bg-card p-4">
                            <p className="text-sm">
                                {due.sentences > 0
                                    ? `Type up to ${due.batch} due sentences from memory of the audio.`
                                    : 'No sentences are due.'}
                                {due.sentences === 0 &&
                                    progress.next_due_at &&
                                    ` The next one is due on ${formatDate(progress.next_due_at)}.`}
                            </p>
                            <Button
                                disabled={due.sentences === 0}
                                onClick={() =>
                                    router.post(
                                        ReviewController.startSentences.url(),
                                    )
                                }
                            >
                                <Play /> Review sentences
                            </Button>
                        </section>

                        {vocabulary.length > 0 && (
                            <section
                                aria-labelledby="words"
                                className="space-y-3"
                            >
                                <h2
                                    id="words"
                                    className="text-lg font-semibold"
                                >
                                    Words to review
                                </h2>
                                <ul className="grid gap-3 md:grid-cols-2">
                                    {vocabulary.map((card) => (
                                        <VocabularyCard
                                            key={card.schedule_id}
                                            card={card}
                                        />
                                    ))}
                                </ul>
                            </section>
                        )}
                    </>
                )}

                {tab === 'history' && history && (
                    <>
                        {history.data.length === 0 ? (
                            <p className="rounded-xl border border-dashed p-8 text-center text-sm text-muted-foreground">
                                No reviews yet. They appear here after your
                                first review.
                            </p>
                        ) : (
                            <div className="rounded-xl border bg-card">
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Item</TableHead>
                                            <TableHead>Result</TableHead>
                                            <TableHead>Step</TableHead>
                                            <TableHead>Date</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {history.data.map((row) => (
                                            <TableRow key={row.id}>
                                                <TableCell className="max-w-xs">
                                                    <span className="line-clamp-1">
                                                        {row.label}
                                                    </span>
                                                    <span className="text-xs text-muted-foreground">
                                                        {row.kind === 'sentence'
                                                            ? 'Sentence'
                                                            : 'Word'}
                                                    </span>
                                                </TableCell>
                                                <TableCell>
                                                    <Badge
                                                        variant="outline"
                                                        className={
                                                            row.is_successful
                                                                ? 'border-success/30 bg-success/10 text-success'
                                                                : 'border-destructive/30 bg-destructive/10 text-destructive'
                                                        }
                                                    >
                                                        {row.is_successful
                                                            ? 'Remembered'
                                                            : 'Forgot'}
                                                        {row.accuracy !==
                                                            null &&
                                                            ` · ${row.accuracy}%`}
                                                    </Badge>
                                                </TableCell>
                                                <TableCell className="tabular-nums">
                                                    {row.previous_step + 1} →{' '}
                                                    {row.new_step + 1}
                                                </TableCell>
                                                <TableCell className="text-muted-foreground">
                                                    {formatDate(
                                                        row.reviewed_at,
                                                    )}
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            </div>
                        )}
                        <Pagination
                            links={history.links}
                            total={history.total}
                        />
                    </>
                )}
            </div>
        </>
    );
}

ReviewIndex.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard() },
        { title: 'Review', href: ReviewController.index() },
    ],
};
