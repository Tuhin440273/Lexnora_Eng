import { usePage } from '@inertiajs/react';

/**
 * Shows or hides UI by permission. This only improves the interface:
 * the server checks the permission again on every request.
 */
export function useCan(permission: string): boolean {
    const { auth } = usePage().props;

    return auth.permissions.includes(permission);
}
