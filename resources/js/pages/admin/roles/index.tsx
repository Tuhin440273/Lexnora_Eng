import { Head, router } from '@inertiajs/react';
import { Lock } from 'lucide-react';
import { useState } from 'react';
import DashboardController from '@/actions/App/Modules/Admin/Http/Controllers/DashboardController';
import RoleController from '@/actions/App/Modules/Admin/Http/Controllers/RoleController';
import { PageHeader } from '@/components/admin/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';

type Role = {
    id: number;
    name: string;
    locked: boolean;
    permissions: string[];
    users: number;
};
type Permission = { name: string; label: string };

function RoleCard({
    role,
    permissions,
}: {
    role: Role;
    permissions: Permission[];
}) {
    const [selected, setSelected] = useState<string[]>(role.permissions);
    const [saving, setSaving] = useState(false);

    const changed =
        selected.length !== role.permissions.length ||
        selected.some((name) => !role.permissions.includes(name));

    const toggle = (name: string) =>
        setSelected((current) =>
            current.includes(name)
                ? current.filter((item) => item !== name)
                : [...current, name],
        );

    const save = () =>
        router.patch(
            RoleController.update.url(role.id),
            { permissions: selected },
            {
                preserveScroll: true,
                onStart: () => setSaving(true),
                onFinish: () => setSaving(false),
            },
        );

    return (
        <section
            className="space-y-3 rounded-xl border bg-card p-4"
            aria-labelledby={`role-${role.id}`}
        >
            <header className="flex flex-wrap items-center justify-between gap-2">
                <h2
                    id={`role-${role.id}`}
                    className="flex items-center gap-2 text-lg font-semibold"
                >
                    {role.name}
                    {role.locked && (
                        <Lock
                            className="size-4 text-muted-foreground"
                            aria-label="Locked"
                        />
                    )}
                </h2>
                <Badge variant="secondary">
                    {role.users} {role.users === 1 ? 'user' : 'users'}
                </Badge>
            </header>

            {role.locked && (
                <p className="text-sm text-muted-foreground">
                    The super-admin always has every permission and cannot be
                    limited.
                </p>
            )}

            <fieldset disabled={role.locked} className="space-y-2">
                <legend className="sr-only">Permissions of {role.name}</legend>
                {permissions.map((permission) => (
                    <label
                        key={permission.name}
                        className="flex items-start gap-2 text-sm"
                    >
                        <input
                            type="checkbox"
                            className="mt-0.5 size-4 accent-primary"
                            checked={
                                role.locked ||
                                selected.includes(permission.name)
                            }
                            onChange={() => toggle(permission.name)}
                        />
                        <span>
                            {permission.label}
                            <span className="block text-xs text-muted-foreground">
                                {permission.name}
                            </span>
                        </span>
                    </label>
                ))}
            </fieldset>

            {!role.locked && (
                <Button onClick={save} disabled={!changed || saving}>
                    Save permissions
                </Button>
            )}
        </section>
    );
}

export default function RolesIndex({
    roles,
    permissions,
}: {
    roles: Role[];
    permissions: Permission[];
}) {
    return (
        <>
            <Head title="Roles" />
            <div className="flex flex-col gap-6 p-4">
                <PageHeader
                    title="Roles and permissions"
                    description="Decide what each role may do. Changes apply the next time the person opens a page."
                />
                <div className="grid gap-4 lg:grid-cols-2">
                    {roles.map((role) => (
                        <RoleCard
                            key={role.id}
                            role={role}
                            permissions={permissions}
                        />
                    ))}
                </div>
            </div>
        </>
    );
}

RolesIndex.layout = {
    breadcrumbs: [
        { title: 'Content admin', href: DashboardController.index() },
        { title: 'Roles', href: RoleController.index() },
    ],
};
