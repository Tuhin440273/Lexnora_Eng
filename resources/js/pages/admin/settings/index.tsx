import { Head, router, useForm } from '@inertiajs/react';
import { RotateCcw } from 'lucide-react';
import type { FormEvent } from 'react';
import DashboardController from '@/actions/App/Modules/Admin/Http/Controllers/DashboardController';
import SettingsController from '@/actions/App/Modules/Admin/Http/Controllers/SettingsController';
import { ConfirmDialog } from '@/components/admin/confirm-dialog';
import { PageHeader } from '@/components/admin/page-header';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { NativeSelect } from '@/components/ui/native-select';

type Field = {
    id: string;
    key: string;
    group: string;
    label: string;
    help?: string;
    type: 'int' | 'float' | 'bool' | 'select' | 'list_int' | 'list_float';
    min?: number;
    max?: number;
    options?: Record<string, string>;
    value: string | number | boolean | null;
    default: string | number | boolean | null;
};

type FormValues = Record<string, string | number | boolean>;

export default function SettingsIndex({ fields }: { fields: Field[] }) {
    const form = useForm<{ values: FormValues }>({
        values: Object.fromEntries(
            fields.map((field) => [field.id, field.value ?? '']),
        ),
    });

    const groups = Array.from(new Set(fields.map((field) => field.group)));

    const set = (id: string, value: string | number | boolean) =>
        form.setData('values', { ...form.data.values, [id]: value });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.put(SettingsController.update.url(), { preserveScroll: true });
    };

    return (
        <>
            <Head title="Settings" />
            <div className="flex max-w-3xl flex-col gap-6 p-4">
                <PageHeader
                    title="Learning settings"
                    description="These rules apply to every learner straight after you save."
                    actions={
                        <ConfirmDialog
                            title="Restore the default settings?"
                            description="Every value on this page goes back to the built-in default."
                            confirmLabel="Restore defaults"
                            onConfirm={() =>
                                router.delete(SettingsController.reset.url(), {
                                    preserveScroll: true,
                                })
                            }
                            trigger={
                                <Button variant="outline">
                                    <RotateCcw /> Restore defaults
                                </Button>
                            }
                        />
                    }
                />

                <form onSubmit={submit} className="space-y-8">
                    {groups.map((group) => (
                        <fieldset
                            key={group}
                            className="space-y-4 rounded-xl border bg-card p-4"
                        >
                            <legend className="px-2 text-base font-semibold">
                                {group}
                            </legend>
                            {fields
                                .filter((field) => field.group === group)
                                .map((field) => {
                                    const error =
                                        form.errors[
                                            `values.${field.id}` as keyof typeof form.errors
                                        ];
                                    const value = form.data.values[field.id];

                                    return (
                                        <div
                                            key={field.id}
                                            className="grid gap-1.5"
                                        >
                                            {field.type === 'bool' ? (
                                                <label className="flex items-center gap-2 text-sm">
                                                    <input
                                                        type="checkbox"
                                                        className="size-4 accent-primary"
                                                        checked={value === true}
                                                        onChange={(event) =>
                                                            set(
                                                                field.id,
                                                                event.target
                                                                    .checked,
                                                            )
                                                        }
                                                    />
                                                    {field.label}
                                                </label>
                                            ) : (
                                                <>
                                                    <Label htmlFor={field.id}>
                                                        {field.label}
                                                    </Label>
                                                    {field.type === 'select' ? (
                                                        <NativeSelect
                                                            id={field.id}
                                                            value={String(
                                                                value,
                                                            )}
                                                            onChange={(event) =>
                                                                set(
                                                                    field.id,
                                                                    event.target
                                                                        .value,
                                                                )
                                                            }
                                                        >
                                                            {Object.entries(
                                                                field.options ??
                                                                    {},
                                                            ).map(
                                                                ([
                                                                    optionValue,
                                                                    label,
                                                                ]) => (
                                                                    <option
                                                                        key={
                                                                            optionValue
                                                                        }
                                                                        value={
                                                                            optionValue
                                                                        }
                                                                    >
                                                                        {label}
                                                                    </option>
                                                                ),
                                                            )}
                                                        </NativeSelect>
                                                    ) : (
                                                        <Input
                                                            id={field.id}
                                                            type={
                                                                field.type ===
                                                                    'int' ||
                                                                field.type ===
                                                                    'float'
                                                                    ? 'number'
                                                                    : 'text'
                                                            }
                                                            min={field.min}
                                                            max={field.max}
                                                            step={
                                                                field.type ===
                                                                'float'
                                                                    ? 'any'
                                                                    : field.type ===
                                                                        'int'
                                                                      ? 1
                                                                      : undefined
                                                            }
                                                            value={String(
                                                                value,
                                                            )}
                                                            onChange={(event) =>
                                                                set(
                                                                    field.id,
                                                                    event.target
                                                                        .value,
                                                                )
                                                            }
                                                            aria-invalid={
                                                                !!error
                                                            }
                                                            className="max-w-xs"
                                                        />
                                                    )}
                                                </>
                                            )}
                                            {field.help && (
                                                <p className="text-xs text-muted-foreground">
                                                    {field.help}
                                                </p>
                                            )}
                                            <InputError message={error} />
                                        </div>
                                    );
                                })}
                        </fieldset>
                    ))}

                    <div className="sticky bottom-4 flex justify-end">
                        <Button
                            type="submit"
                            size="lg"
                            disabled={form.processing}
                        >
                            Save settings
                        </Button>
                    </div>
                </form>
            </div>
        </>
    );
}

SettingsIndex.layout = {
    breadcrumbs: [
        { title: 'Content admin', href: DashboardController.index() },
        { title: 'Settings', href: SettingsController.edit() },
    ],
};
