import { router } from '@inertiajs/react';
import { ChevronDown, ChevronUp } from 'lucide-react';
import { Button } from '@/components/ui/button';

/**
 * Up / down arrows that call a PATCH "move" endpoint.
 */
export function MoveButtons({
    url,
    label,
    isFirst,
    isLast,
}: {
    url: string;
    label: string;
    isFirst: boolean;
    isLast: boolean;
}) {
    const move = (direction: 'up' | 'down') =>
        router.patch(url, { direction }, { preserveScroll: true });

    return (
        <div className="flex">
            <Button
                type="button"
                variant="ghost"
                size="icon"
                className="size-8"
                disabled={isFirst}
                onClick={() => move('up')}
                aria-label={`Move ${label} up`}
            >
                <ChevronUp />
            </Button>
            <Button
                type="button"
                variant="ghost"
                size="icon"
                className="size-8"
                disabled={isLast}
                onClick={() => move('down')}
                aria-label={`Move ${label} down`}
            >
                <ChevronDown />
            </Button>
        </div>
    );
}
