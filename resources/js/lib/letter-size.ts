/**
 * Letter size for the practice screen (the faint guide text and the typing
 * box). The choice is kept per device in the browser.
 */
export const LETTER_SIZES = ['normal', 'large', 'larger'] as const;

export type LetterSize = (typeof LETTER_SIZES)[number];

export const LETTER_SIZE_STORAGE_KEY = 'letter-size';

export const LETTER_SIZE_CLASSES: Record<
    LetterSize,
    { guide: string; input: string }
> = {
    normal: { guide: 'text-xl', input: 'text-lg' },
    large: { guide: 'text-3xl', input: 'text-2xl' },
    larger: { guide: 'text-4xl', input: 'text-3xl' },
};

export function parseLetterSize(value: string | null | undefined): LetterSize {
    return LETTER_SIZES.find((size) => size === value) ?? 'normal';
}

/** One step up (+1) or down (-1), never past the first or last size. */
export function stepLetterSize(current: LetterSize, step: 1 | -1): LetterSize {
    const index = LETTER_SIZES.indexOf(current) + step;

    return LETTER_SIZES[Math.min(Math.max(index, 0), LETTER_SIZES.length - 1)];
}
