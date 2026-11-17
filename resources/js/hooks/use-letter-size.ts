import { useSyncExternalStore } from 'react';
import {
    LETTER_SIZE_STORAGE_KEY,
    parseLetterSize,
    stepLetterSize,
} from '@/lib/letter-size';
import type { LetterSize } from '@/lib/letter-size';

const listeners = new Set<() => void>();

const subscribe = (callback: () => void) => {
    listeners.add(callback);

    return () => listeners.delete(callback);
};

const read = (): LetterSize => {
    try {
        return parseLetterSize(localStorage.getItem(LETTER_SIZE_STORAGE_KEY));
    } catch {
        return 'normal';
    }
};

const write = (size: LetterSize): void => {
    try {
        localStorage.setItem(LETTER_SIZE_STORAGE_KEY, size);
    } catch {
        // Storage can be blocked; the size then lasts until the page closes.
    }

    listeners.forEach((listener) => listener());
};

/** The learner's letter size, kept per device. */
export function useLetterSize() {
    const size = useSyncExternalStore(subscribe, read, () => 'normal' as const);

    return {
        size,
        bigger: () => write(stepLetterSize(read(), 1)),
        smaller: () => write(stepLetterSize(read(), -1)),
    };
}
