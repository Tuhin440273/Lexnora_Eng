import { useState } from 'react';
import type { ReactNode } from 'react';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';

/**
 * A dialog that owns its open state. The content renderer receives a
 * `close` function, to be called when the form was saved.
 */
export function FormDialog({
    trigger,
    title,
    description,
    children,
}: {
    trigger: ReactNode;
    title: string;
    description?: string;
    children: (close: () => void) => ReactNode;
}) {
    const [open, setOpen] = useState(false);

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>{trigger}</DialogTrigger>
            <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>{title}</DialogTitle>
                    {description && (
                        <DialogDescription>{description}</DialogDescription>
                    )}
                </DialogHeader>
                {children(() => setOpen(false))}
            </DialogContent>
        </Dialog>
    );
}
