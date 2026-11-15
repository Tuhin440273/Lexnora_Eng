import { Form } from '@inertiajs/react';
import { Upload } from 'lucide-react';
import { useState } from 'react';
import type { ChangeEvent } from 'react';
import SentenceAudioController from '@/actions/App/Modules/Admin/Http/Controllers/SentenceAudioController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { AUDIO_ACCEPT, readAudioDuration } from '@/lib/audio';

export function AudioUploader({
    sentenceId,
    label,
}: {
    sentenceId: string;
    label: string;
}) {
    const [duration, setDuration] = useState<number | null>(null);

    const onFileChange = async (event: ChangeEvent<HTMLInputElement>) => {
        const file = event.target.files?.[0];

        setDuration(file ? await readAudioDuration(file) : null);
    };

    return (
        <Form
            {...SentenceAudioController.store.form(sentenceId)}
            options={{ preserveScroll: true }}
            resetOnSuccess
            onSuccess={() => setDuration(null)}
            className="flex flex-wrap items-center gap-2"
        >
            {({ errors, processing }) => (
                <>
                    <Input
                        type="file"
                        name="audio"
                        accept={AUDIO_ACCEPT}
                        required
                        onChange={onFileChange}
                        aria-label={`Audio file for ${label}`}
                        aria-invalid={!!errors.audio}
                        className="h-9 max-w-64"
                    />
                    <input
                        type="hidden"
                        name="duration_ms"
                        value={duration ?? ''}
                    />
                    <label className="flex items-center gap-1.5 text-xs text-muted-foreground">
                        <input
                            type="checkbox"
                            name="make_primary"
                            value="1"
                            className="size-3.5 accent-primary"
                        />
                        Use as primary
                    </label>
                    <Button
                        type="submit"
                        size="sm"
                        variant="secondary"
                        disabled={processing}
                    >
                        <Upload /> Upload
                    </Button>
                    <div className="w-full">
                        <InputError message={errors.audio} />
                        <InputError message={errors.duration_ms} />
                    </div>
                </>
            )}
        </Form>
    );
}
