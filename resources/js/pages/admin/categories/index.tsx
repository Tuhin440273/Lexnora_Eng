import { Form, Head } from '@inertiajs/react';
import { router } from '@inertiajs/react';
import { Pencil, Plus, Trash2 } from 'lucide-react';
import CategoryController from '@/actions/App/Modules/Admin/Http/Controllers/CategoryController';
import DashboardController from '@/actions/App/Modules/Admin/Http/Controllers/DashboardController';
import { ConfirmDialog } from '@/components/admin/confirm-dialog';
import { FormDialog } from '@/components/admin/form-dialog';
import { PageHeader } from '@/components/admin/page-header';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Textarea } from '@/components/ui/textarea';
import { useCan } from '@/hooks/use-can';
import type { Category } from '@/types';

type FormProps = {
    category?: Category;
    onSaved: () => void;
};

function CategoryForm({ category, onSaved }: FormProps) {
    const formProps = category
        ? CategoryController.update.form(category.id)
        : CategoryController.store.form();

    return (
        <Form
            {...formProps}
            options={{ preserveScroll: true }}
            onSuccess={onSaved}
            className="space-y-4"
        >
            {({ errors, processing }) => (
                <>
                    <div className="grid gap-2">
                        <Label htmlFor="category-name">Name</Label>
                        <Input
                            id="category-name"
                            name="name"
                            defaultValue={category?.name ?? ''}
                            required
                            maxLength={100}
                            aria-invalid={!!errors.name}
                        />
                        <InputError message={errors.name} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="category-slug">
                            Web address (slug)
                        </Label>
                        <Input
                            id="category-slug"
                            name="slug"
                            defaultValue={category?.slug ?? ''}
                            placeholder="Leave empty to create it from the name"
                            aria-invalid={!!errors.slug}
                        />
                        <InputError message={errors.slug} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="category-description">
                            Description
                        </Label>
                        <Textarea
                            id="category-description"
                            name="description"
                            defaultValue={category?.description ?? ''}
                            rows={3}
                            aria-invalid={!!errors.description}
                        />
                        <InputError message={errors.description} />
                    </div>

                    <label className="flex items-center gap-2 text-sm">
                        <input
                            type="checkbox"
                            name="is_active"
                            value="1"
                            defaultChecked={category?.is_active ?? true}
                            className="size-4 accent-primary"
                        />
                        Active (can be picked for new courses)
                    </label>

                    <div className="flex justify-end">
                        <Button type="submit" disabled={processing}>
                            {category ? 'Save changes' : 'Create category'}
                        </Button>
                    </div>
                </>
            )}
        </Form>
    );
}

export default function CategoriesIndex({
    categories,
}: {
    categories: Category[];
}) {
    const canManage = useCan('content.manage');

    return (
        <>
            <Head title="Categories" />
            <div className="flex flex-col gap-6 p-4">
                <PageHeader
                    title="Categories"
                    description="Categories group courses, for example Daily Life or Travel."
                    actions={
                        canManage && (
                            <FormDialog
                                title="New category"
                                trigger={
                                    <Button>
                                        <Plus /> New category
                                    </Button>
                                }
                            >
                                {(close) => <CategoryForm onSaved={close} />}
                            </FormDialog>
                        )
                    }
                />

                {categories.length === 0 ? (
                    <p className="rounded-xl border border-dashed p-8 text-center text-sm text-muted-foreground">
                        No categories yet. Create one, then pick it when you add
                        a course.
                    </p>
                ) : (
                    <div className="rounded-xl border bg-card">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Name</TableHead>
                                    <TableHead>Slug</TableHead>
                                    <TableHead>Courses</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead className="text-right">
                                        Actions
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {categories.map((category) => (
                                    <TableRow key={category.id}>
                                        <TableCell>
                                            <div className="font-medium">
                                                {category.name}
                                            </div>
                                            {category.description && (
                                                <div className="line-clamp-1 max-w-md text-xs text-muted-foreground">
                                                    {category.description}
                                                </div>
                                            )}
                                        </TableCell>
                                        <TableCell className="text-muted-foreground">
                                            {category.slug}
                                        </TableCell>
                                        <TableCell className="tabular-nums">
                                            {category.courses_count ?? 0}
                                        </TableCell>
                                        <TableCell>
                                            <Badge
                                                variant="outline"
                                                className={
                                                    category.is_active
                                                        ? 'border-success/30 bg-success/10 text-success'
                                                        : ''
                                                }
                                            >
                                                {category.is_active
                                                    ? 'Active'
                                                    : 'Inactive'}
                                            </Badge>
                                        </TableCell>
                                        <TableCell>
                                            {canManage && (
                                                <div className="flex justify-end gap-1">
                                                    <FormDialog
                                                        title="Edit category"
                                                        trigger={
                                                            <Button
                                                                variant="ghost"
                                                                size="icon"
                                                                aria-label={`Edit ${category.name}`}
                                                            >
                                                                <Pencil />
                                                            </Button>
                                                        }
                                                    >
                                                        {(close) => (
                                                            <CategoryForm
                                                                category={
                                                                    category
                                                                }
                                                                onSaved={close}
                                                            />
                                                        )}
                                                    </FormDialog>
                                                    <ConfirmDialog
                                                        title={`Delete "${category.name}"?`}
                                                        description="A category that still has courses cannot be deleted."
                                                        onConfirm={() =>
                                                            router.delete(
                                                                CategoryController.destroy.url(
                                                                    category.id,
                                                                ),
                                                                {
                                                                    preserveScroll: true,
                                                                },
                                                            )
                                                        }
                                                        trigger={
                                                            <Button
                                                                variant="ghost"
                                                                size="icon"
                                                                aria-label={`Delete ${category.name}`}
                                                            >
                                                                <Trash2 />
                                                            </Button>
                                                        }
                                                    />
                                                </div>
                                            )}
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

CategoriesIndex.layout = {
    breadcrumbs: [
        { title: 'Content admin', href: DashboardController.index() },
        { title: 'Categories', href: CategoryController.index() },
    ],
};
