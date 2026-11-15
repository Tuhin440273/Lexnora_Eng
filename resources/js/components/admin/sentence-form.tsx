import { Form } from '@inertiajs/react';
import SentenceController from '@/actions/App/Modules/Admin/Http/Controllers/SentenceController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { NativeSelect } from '@/components/ui/native-select';
import { Textarea } from '@/components/ui/textarea';
import type { ContentType, Sentence } from '@/types';

const ALL_TYPES: { value: ContentType; label: string }[] = [
    { value: 'sentence', label: 'Sentence' },
    { value: 'phrase', label: 'Phrase' },
    { value: 'word', label: 'Word' },
];

const CHUNK_TYPES = ALL_TYPES.filter((type) => type.value !== 'sentence');

export function SentenceForm({
    lessonId,
    sentence,
    parentId,
    onSaved,
}: {
    lessonId: string;
    sentence?: Sentence;
    /** Set when adding a word or phrase that belongs to a sentence. */
    parentId?: string;
    onSaved: () => void;
}) {
    const isChunk = parentId !== undefined || sentence?.parent_id != null;
    const types = isChunk ? CHUNK_TYPES : ALL_TYPES;

    const formProps = sentence
        ? SentenceController.update.form(sentence.id)
        : SentenceController.store.form(lessonId);

    return (
        <Form
            {...formProps}
            options={{ preserveScroll: true }}
            onSuccess={onSaved}
            className="space-y-4"
        >
            {({ errors, processing }) => (
                <>
                    {parentId && (
                        <input
                            type="hidden"
                            name="parent_id"
                            value={parentId}
                        />
                    )}

                    <div className="grid gap-2">
                        <Label htmlFor="content_type">Type</Label>
                        <NativeSelect
                            id="content_type"
                            name="content_type"
                            defaultValue={
                                sentence?.content_type ??
                                (isChunk ? 'word' : 'sentence')
                            }
                        >
                            {types.map((type) => (
                                <option key={type.value} value={type.value}>
                                    {type.label}
                                </option>
                            ))}
                        </NativeSelect>
                        <InputError message={errors.content_type} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="english_text">English text</Label>
                        <Textarea
                            id="english_text"
                            name="english_text"
                            rows={2}
                            defaultValue={sentence?.english_text ?? ''}
                            required
                            maxLength={1000}
                            aria-invalid={!!errors.english_text}
                        />
                        <InputError message={errors.english_text} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="bengali_text">
                            Bengali translation (optional)
                        </Label>
                        <Input
                            id="bengali_text"
                            name="bengali_text"
                            lang="bn"
                            defaultValue={sentence?.bengali_text ?? ''}
                            aria-invalid={!!errors.bengali_text}
                        />
                        <InputError message={errors.bengali_text} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="explanation">
                            Explanation (optional)
                        </Label>
                        <Textarea
                            id="explanation"
                            name="explanation"
                            rows={2}
                            defaultValue={sentence?.explanation ?? ''}
                            aria-invalid={!!errors.explanation}
                        />
                        <InputError message={errors.explanation} />
                    </div>

                    <div className="flex justify-end">
                        <Button type="submit" disabled={processing}>
                            {sentence ? 'Save changes' : 'Add'}
                        </Button>
                    </div>
                </>
            )}
        </Form>
    );
}
