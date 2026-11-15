import { cn } from '@/lib/utils';
import type { DiffToken } from '@/types';

/**
 * Shows the typed text against the expected text, word by word. Meaning is
 * carried by words and symbols as well as by colour.
 */
export function DiffView({
    tokens,
    className,
}: {
    tokens: DiffToken[];
    className?: string;
}) {
    return (
        <p className={cn('text-lg leading-relaxed', className)}>
            {tokens.map((token, index) => (
                <span key={index} className="mr-1.5 inline-block">
                    {token.type === 'correct' && <span>{token.expected}</span>}

                    {token.type === 'incorrect' && (
                        <>
                            <span className="sr-only">You typed </span>
                            <span className="text-destructive line-through decoration-2">
                                {token.typed}
                            </span>
                            <span className="sr-only">, should be </span>
                            <span className="ml-1 rounded bg-success/15 px-1 font-medium text-success">
                                {token.expected}
                            </span>
                        </>
                    )}

                    {token.type === 'missing' && (
                        <>
                            <span className="sr-only">Missing word: </span>
                            <span className="rounded border border-dashed border-warning bg-warning/15 px-1 font-medium">
                                {token.expected}
                            </span>
                        </>
                    )}

                    {token.type === 'extra' && (
                        <>
                            <span className="sr-only">Extra word: </span>
                            <span className="text-destructive line-through decoration-2">
                                {token.typed}
                            </span>
                        </>
                    )}
                </span>
            ))}
        </p>
    );
}

export function DiffLegend() {
    return (
        <ul className="flex flex-wrap gap-x-4 gap-y-1 text-xs text-muted-foreground">
            <li>
                <span className="text-destructive line-through">typed</span>{' '}
                wrong or extra
            </li>
            <li>
                <span className="rounded bg-success/15 px-1 text-success">
                    word
                </span>{' '}
                what it should be
            </li>
            <li>
                <span className="rounded border border-dashed border-warning px-1">
                    word
                </span>{' '}
                missing
            </li>
        </ul>
    );
}
