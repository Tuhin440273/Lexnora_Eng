import { Form, Head, router } from '@inertiajs/react';
import { Link2, Pencil, Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import DashboardController from '@/actions/App/Modules/Admin/Http/Controllers/DashboardController';
import VocabularyController from '@/actions/App/Modules/Admin/Http/Controllers/VocabularyController';
import { ConfirmDialog } from '@/components/admin/confirm-dialog';
import { FormDialog } from '@/components/admin/form-dialog';
import { PageHeader } from '@/components/admin/page-header';
import { Pagination } from '@/components/admin/pagination';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { NativeSelect } from '@/components/ui/native-select';
import { Textarea } from '@/components/ui/textarea';
import type { Paginated, SelectOption } from '@/types';

type Entry = {
    id: string;
    term: string;
    type: 'word' | 'phrase';
    part_of_speech: string | null;
    meaning_en: string | null;
    meaning_bn: string | null;
    pronunciation: string | null;
    example: string | null;
    cefr_level: string | null;
    is_active: boolean;
    sentences_count: number;
};

type Filters = {
    search: string;
    type: string;
    cefr_level: string;
    status: string;
};

function EntryForm({
    entry,
    levels,
    onSaved,
}: {
    entry?: Entry;
    levels: SelectOption[];
    onSaved: () => void;
}) {
    const formProps = entry
        ? VocabularyController.update.form(entry.id)
        : VocabularyController.store.form();

    return (
        <Form
            {...formProps}
            options={{ preserveScroll: true }}
            onSuccess={onSaved}
            className="space-y-4"
        >
            {({ errors, processing }) => (
                <>
                    <div className="grid gap-4 sm:grid-cols-3">
                        <div className="grid gap-2 sm:col-span-2">
                            <Label htmlFor="term">Word or phrase</Label>
                            <Input
                                id="term"
                                name="term"
                                defaultValue={entry?.term ?? ''}
                                required
                                maxLength={150}
                                aria-invalid={
                                    !!errors.term || !!errors.normalized_term
                                }
                            />
                            <InputError
                                message={errors.term ?? errors.normalized_term}
                            />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="type">Type</Label>
                            <NativeSelect
                                id="type"
                                name="type"
                                defaultValue={entry?.type ?? 'word'}
                            >
                                <option value="word">Word</option>
                                <option value="phrase">Phrase</option>
                            </NativeSelect>
                            <InputError message={errors.type} />
                        </div>
                    </div>

                    <div className="grid gap-4 sm:grid-cols-3">
                        <div className="grid gap-2">
                            <Label htmlFor="part_of_speech">
                                Part of speech
                            </Label>
                            <Input
                                id="part_of_speech"
                                name="part_of_speech"
                                defaultValue={entry?.part_of_speech ?? ''}
                                placeholder="noun, verb..."
                            />
                            <InputError message={errors.part_of_speech} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="pronunciation">Pronunciation</Label>
                            <Input
                                id="pronunciation"
                                name="pronunciation"
                                defaultValue={entry?.pronunciation ?? ''}
                                placeholder="/ˈkɒfi/"
                            />
                            <InputError message={errors.pronunciation} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="cefr_level">Level</Label>
                            <NativeSelect
                                id="cefr_level"
                                name="cefr_level"
                                defaultValue={entry?.cefr_level ?? ''}
                            >
                                <option value="">Not set</option>
                                {levels.map((level) => (
                                    <option
                                        key={level.value}
                                        value={level.value}
                                    >
                                        {level.label}
                                    </option>
                                ))}
                            </NativeSelect>
                            <InputError message={errors.cefr_level} />
                        </div>
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="meaning_en">Meaning in English</Label>
                        <Textarea
                            id="meaning_en"
                            name="meaning_en"
                            rows={2}
                            defaultValue={entry?.meaning_en ?? ''}
                        />
                        <InputError message={errors.meaning_en} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="meaning_bn">Meaning in Bengali</Label>
                        <Textarea
                            id="meaning_bn"
                            name="meaning_bn"
                            lang="bn"
                            rows={2}
                            defaultValue={entry?.meaning_bn ?? ''}
                        />
                        <InputError message={errors.meaning_bn} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="example">Example sentence</Label>
                        <Input
                            id="example"
                            name="example"
                            defaultValue={entry?.example ?? ''}
                        />
                        <InputError message={errors.example} />
                    </div>

                    <label className="flex items-center gap-2 text-sm">
                        <input
                            type="checkbox"
                            name="is_active"
                            value="1"
                            defaultChecked={entry?.is_active ?? true}
                            className="size-4 accent-primary"
                        />
                        Active (linked to sentences and offered to learners)
                    </label>

                    <div className="flex justify-end">
                        <Button type="submit" disabled={processing}>
                            {entry ? 'Save changes' : 'Add to the list'}
                        </Button>
                    </div>
                </>
            )}
        </Form>
    );
}

export default function VocabularyAdmin({
    entries,
    filters,
    cefr_levels,
}: {
    entries: Paginated<Entry>;
    filters: Filters;
    cefr_levels: SelectOption[];
}) {
    const [values, setValues] = useState<Filters>(filters);

    const apply = (next: Filters) => {
        setValues(next);
        router.get(
            VocabularyController.index().url,
            Object.fromEntries(
                Object.entries(next).filter(([, v]) => v !== ''),
            ),
            { preserveState: true, replace: true },
        );
    };

    return (
        <>
            <Head title="Vocabulary list" />
            <div className="flex flex-col gap-6 p-4">
                <PageHeader
                    title="Vocabulary list"
                    description="Words and phrases with their meanings. Each one is linked to the sentences that use it, and learners see the meaning when they save the word."
                    actions={
                        <FormDialog
                            title="Add a word or phrase"
                            trigger={
                                <Button>
                                    <Plus /> Add entry
                                </Button>
                            }
                        >
                            {(close) => (
                                <EntryForm
                                    levels={cefr_levels}
                                    onSaved={close}
                                />
                            )}
                        </FormDialog>
                    }
                />

                <form
                    role="search"
                    className="flex flex-wrap gap-3"
                    onSubmit={(event: FormEvent) => {
                        event.preventDefault();
                        apply(values);
                    }}
                >
                    <label htmlFor="vocab-search" className="sr-only">
                        Search
                    </label>
                    <Input
                        id="vocab-search"
                        type="search"
                        placeholder="Search words and meanings"
                        className="min-w-56 flex-1"
                        value={values.search}
                        onChange={(event) =>
                            setValues({ ...values, search: event.target.value })
                        }
                    />
                    <label htmlFor="vocab-type" className="sr-only">
                        Type
                    </label>
                    <NativeSelect
                        id="vocab-type"
                        className="w-36"
                        value={values.type}
                        onChange={(event) =>
                            apply({ ...values, type: event.target.value })
                        }
                    >
                        <option value="">Words and phrases</option>
                        <option value="word">Words</option>
                        <option value="phrase">Phrases</option>
                    </NativeSelect>
                    <label htmlFor="vocab-level" className="sr-only">
                        Level
                    </label>
                    <NativeSelect
                        id="vocab-level"
                        className="w-44"
                        value={values.cefr_level}
                        onChange={(event) =>
                            apply({ ...values, cefr_level: event.target.value })
                        }
                    >
                        <option value="">All levels</option>
                        {cefr_levels.map((level) => (
                            <option key={level.value} value={level.value}>
                                {level.label}
                            </option>
                        ))}
                    </NativeSelect>
                    <label htmlFor="vocab-status" className="sr-only">
                        Status
                    </label>
                    <NativeSelect
                        id="vocab-status"
                        className="w-36"
                        value={values.status}
                        onChange={(event) =>
                            apply({ ...values, status: event.target.value })
                        }
                    >
                        <option value="">Any status</option>
                        <option value="active">Active</option>
                        <option value="hidden">Hidden</option>
                    </NativeSelect>
                    <Button type="submit" variant="secondary">
                        Search
                    </Button>
                </form>

                {entries.data.length === 0 ? (
                    <p className="rounded-xl border border-dashed p-8 text-center text-sm text-muted-foreground">
                        {Object.values(filters).some((v) => v !== '')
                            ? 'Nothing matches these filters.'
                            : 'The list is empty. Add the first word or phrase.'}
                    </p>
                ) : (
                    <ul className="space-y-3">
                        {entries.data.map((entry) => (
                            <li
                                key={entry.id}
                                className="space-y-2 rounded-xl border bg-card p-4"
                            >
                                <div className="flex flex-wrap items-start justify-between gap-2">
                                    <div className="flex flex-wrap items-center gap-2">
                                        <span className="text-lg font-semibold">
                                            {entry.term}
                                        </span>
                                        <Badge variant="secondary">
                                            {entry.type}
                                        </Badge>
                                        {entry.cefr_level && (
                                            <Badge variant="outline">
                                                {entry.cefr_level}
                                            </Badge>
                                        )}
                                        {entry.part_of_speech && (
                                            <span className="text-sm text-muted-foreground">
                                                {entry.part_of_speech}
                                            </span>
                                        )}
                                        {entry.pronunciation && (
                                            <span className="text-sm text-muted-foreground">
                                                {entry.pronunciation}
                                            </span>
                                        )}
                                        {!entry.is_active && (
                                            <Badge variant="outline">
                                                Hidden
                                            </Badge>
                                        )}
                                    </div>
                                    <div className="flex items-center gap-1">
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            onClick={() =>
                                                router.post(
                                                    VocabularyController.relink.url(
                                                        entry.id,
                                                    ),
                                                    {},
                                                    { preserveScroll: true },
                                                )
                                            }
                                            disabled={!entry.is_active}
                                        >
                                            <Link2 /> Link sentences
                                        </Button>
                                        <FormDialog
                                            title={`Edit "${entry.term}"`}
                                            trigger={
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    aria-label={`Edit ${entry.term}`}
                                                >
                                                    <Pencil />
                                                </Button>
                                            }
                                        >
                                            {(close) => (
                                                <EntryForm
                                                    entry={entry}
                                                    levels={cefr_levels}
                                                    onSaved={close}
                                                />
                                            )}
                                        </FormDialog>
                                        <ConfirmDialog
                                            title={`Remove "${entry.term}"?`}
                                            description="It leaves the list and its sentence links. Words learners already saved stay in their notebooks."
                                            confirmLabel="Remove"
                                            onConfirm={() =>
                                                router.delete(
                                                    VocabularyController.destroy.url(
                                                        entry.id,
                                                    ),
                                                    { preserveScroll: true },
                                                )
                                            }
                                            trigger={
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    aria-label={`Remove ${entry.term}`}
                                                >
                                                    <Trash2 />
                                                </Button>
                                            }
                                        />
                                    </div>
                                </div>
                                {(entry.meaning_en || entry.meaning_bn) && (
                                    <p className="text-sm">
                                        {entry.meaning_en && (
                                            <span className="mr-3">
                                                {entry.meaning_en}
                                            </span>
                                        )}
                                        {entry.meaning_bn && (
                                            <span
                                                lang="bn"
                                                className="text-muted-foreground"
                                            >
                                                {entry.meaning_bn}
                                            </span>
                                        )}
                                    </p>
                                )}
                                {entry.example && (
                                    <p className="text-sm text-muted-foreground italic">
                                        &ldquo;{entry.example}&rdquo;
                                    </p>
                                )}
                                <p className="text-xs text-muted-foreground">
                                    Used in {entry.sentences_count} sentence
                                    {entry.sentences_count === 1 ? '' : 's'}
                                </p>
                            </li>
                        ))}
                    </ul>
                )}

                <Pagination links={entries.links} total={entries.total} />
            </div>
        </>
    );
}

VocabularyAdmin.layout = {
    breadcrumbs: [
        { title: 'Content admin', href: DashboardController.index() },
        { title: 'Vocabulary', href: VocabularyController.index() },
    ],
};
