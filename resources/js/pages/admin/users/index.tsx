import { Head, router } from '@inertiajs/react';
import { UserCheck, UserX } from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import DashboardController from '@/actions/App/Modules/Admin/Http/Controllers/DashboardController';
import UserController from '@/actions/App/Modules/Admin/Http/Controllers/UserController';
import { ConfirmDialog } from '@/components/admin/confirm-dialog';
import { PageHeader } from '@/components/admin/page-header';
import { Pagination } from '@/components/admin/pagination';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { NativeSelect } from '@/components/ui/native-select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { useCan } from '@/hooks/use-can';
import { formatDate } from '@/lib/learn';
import type { Paginated } from '@/types';

type Row = {
    id: number;
    name: string;
    email: string;
    verified: boolean;
    role: string | null;
    is_active: boolean;
    is_me: boolean;
    joined_at: string | null;
};

type Filters = { search: string; role: string; status: string };

export default function UsersIndex({
    users,
    filters,
    roles,
}: {
    users: Paginated<Row>;
    filters: Filters;
    roles: string[];
}) {
    const canChangeRoles = useCan('roles.manage');
    const [values, setValues] = useState<Filters>(filters);

    const apply = (next: Filters) => {
        setValues(next);
        router.get(
            UserController.index().url,
            Object.fromEntries(
                Object.entries(next).filter(([, v]) => v !== ''),
            ),
            { preserveState: true, replace: true },
        );
    };

    return (
        <>
            <Head title="Users" />
            <div className="flex flex-col gap-6 p-4">
                <PageHeader
                    title="Users"
                    description="Find accounts, change roles and deactivate people who should no longer sign in."
                />

                <form
                    role="search"
                    className="flex flex-wrap gap-3"
                    onSubmit={(event: FormEvent) => {
                        event.preventDefault();
                        apply(values);
                    }}
                >
                    <label htmlFor="user-search" className="sr-only">
                        Search by name or email
                    </label>
                    <Input
                        id="user-search"
                        type="search"
                        placeholder="Search by name or email"
                        className="min-w-56 flex-1"
                        value={values.search}
                        onChange={(event) =>
                            setValues({ ...values, search: event.target.value })
                        }
                    />
                    <label htmlFor="user-role" className="sr-only">
                        Role
                    </label>
                    <NativeSelect
                        id="user-role"
                        className="w-44"
                        value={values.role}
                        onChange={(event) =>
                            apply({ ...values, role: event.target.value })
                        }
                    >
                        <option value="">All roles</option>
                        {roles.map((role) => (
                            <option key={role} value={role}>
                                {role}
                            </option>
                        ))}
                    </NativeSelect>
                    <label htmlFor="user-status" className="sr-only">
                        Status
                    </label>
                    <NativeSelect
                        id="user-status"
                        className="w-40"
                        value={values.status}
                        onChange={(event) =>
                            apply({ ...values, status: event.target.value })
                        }
                    >
                        <option value="">Any status</option>
                        <option value="active">Active</option>
                        <option value="inactive">Deactivated</option>
                    </NativeSelect>
                    <Button type="submit" variant="secondary">
                        Search
                    </Button>
                </form>

                {users.data.length === 0 ? (
                    <p className="rounded-xl border border-dashed p-8 text-center text-sm text-muted-foreground">
                        No user matches these filters.
                    </p>
                ) : (
                    <div className="rounded-xl border bg-card">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Name</TableHead>
                                    <TableHead>Role</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead>Joined</TableHead>
                                    <TableHead className="text-right">
                                        Actions
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {users.data.map((user) => (
                                    <TableRow key={user.id}>
                                        <TableCell>
                                            <div className="font-medium">
                                                {user.name}
                                                {user.is_me && (
                                                    <span className="ml-2 text-xs text-muted-foreground">
                                                        (you)
                                                    </span>
                                                )}
                                            </div>
                                            <div className="text-xs text-muted-foreground">
                                                {user.email}
                                                {!user.verified &&
                                                    ' · email not verified'}
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            {canChangeRoles && !user.is_me ? (
                                                <>
                                                    <label
                                                        htmlFor={`role-${user.id}`}
                                                        className="sr-only"
                                                    >
                                                        Role of {user.name}
                                                    </label>
                                                    <NativeSelect
                                                        id={`role-${user.id}`}
                                                        className="w-40"
                                                        value={user.role ?? ''}
                                                        onChange={(event) =>
                                                            router.patch(
                                                                UserController.updateRole.url(
                                                                    user.id,
                                                                ),
                                                                {
                                                                    role: event
                                                                        .target
                                                                        .value,
                                                                },
                                                                {
                                                                    preserveScroll: true,
                                                                },
                                                            )
                                                        }
                                                    >
                                                        {!user.role && (
                                                            <option value="">
                                                                No role
                                                            </option>
                                                        )}
                                                        {roles.map((role) => (
                                                            <option
                                                                key={role}
                                                                value={role}
                                                            >
                                                                {role}
                                                            </option>
                                                        ))}
                                                    </NativeSelect>
                                                </>
                                            ) : (
                                                <Badge variant="secondary">
                                                    {user.role ?? 'no role'}
                                                </Badge>
                                            )}
                                        </TableCell>
                                        <TableCell>
                                            <Badge
                                                variant="outline"
                                                className={
                                                    user.is_active
                                                        ? 'border-success/30 bg-success/10 text-success'
                                                        : 'border-destructive/30 bg-destructive/10 text-destructive'
                                                }
                                            >
                                                {user.is_active
                                                    ? 'Active'
                                                    : 'Deactivated'}
                                            </Badge>
                                        </TableCell>
                                        <TableCell className="text-muted-foreground">
                                            {formatDate(user.joined_at)}
                                        </TableCell>
                                        <TableCell>
                                            <div className="flex justify-end">
                                                {!user.is_me &&
                                                    (user.is_active ? (
                                                        <ConfirmDialog
                                                            title={`Deactivate ${user.name}?`}
                                                            description="They are signed out and cannot sign in again until you reactivate the account. Their progress is kept."
                                                            confirmLabel="Deactivate"
                                                            onConfirm={() =>
                                                                router.patch(
                                                                    UserController.toggleActive.url(
                                                                        user.id,
                                                                    ),
                                                                    {},
                                                                    {
                                                                        preserveScroll: true,
                                                                    },
                                                                )
                                                            }
                                                            trigger={
                                                                <Button
                                                                    variant="ghost"
                                                                    size="sm"
                                                                >
                                                                    <UserX />{' '}
                                                                    Deactivate
                                                                </Button>
                                                            }
                                                        />
                                                    ) : (
                                                        <Button
                                                            variant="ghost"
                                                            size="sm"
                                                            onClick={() =>
                                                                router.patch(
                                                                    UserController.toggleActive.url(
                                                                        user.id,
                                                                    ),
                                                                    {},
                                                                    {
                                                                        preserveScroll: true,
                                                                    },
                                                                )
                                                            }
                                                        >
                                                            <UserCheck />{' '}
                                                            Reactivate
                                                        </Button>
                                                    ))}
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </div>
                )}

                <Pagination links={users.links} total={users.total} />
            </div>
        </>
    );
}

UsersIndex.layout = {
    breadcrumbs: [
        { title: 'Content admin', href: DashboardController.index() },
        { title: 'Users', href: UserController.index() },
    ],
};
