export function formatStudyTime(seconds: number): string {
    if (seconds < 60) {
        return `${seconds} s`;
    }

    const minutes = Math.floor(seconds / 60);

    if (minutes < 60) {
        return `${minutes} min`;
    }

    const hours = Math.floor(minutes / 60);
    const rest = minutes % 60;

    return rest === 0 ? `${hours} h` : `${hours} h ${rest} min`;
}

export function formatPercent(value: number | null): string {
    return value === null ? '-' : `${Math.round(value * 10) / 10}%`;
}

export function formatDate(iso: string | null): string {
    if (!iso) {
        return '-';
    }

    return new Date(iso).toLocaleDateString(undefined, {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });
}

/** The words of a sentence without punctuation, for the "save a word" buttons. */
export function wordsOf(text: string): string[] {
    const words = (text.match(/[\p{L}\p{N}][\p{L}\p{N}'’-]*/gu) ?? []).map(
        (word) => word.replace(/^['’-]+|['’-]+$/g, ''),
    );

    return Array.from(new Set(words.map((word) => word.toLowerCase())));
}

export const MODE_INFO = {
    easy: 'Words first, then phrases, then the full sentence. Up to 3 hints.',
    medium: 'Short phrases first, then the full sentence. One hint.',
    hard: 'Full sentences only, no hints.',
} as const;

/** "tomorrow", "in 3 days": when a sentence comes back for review. */
export function describeNextReview(
    review: { days: number; mastered: boolean } | null,
): string | null {
    if (review === null) {
        return null;
    }

    if (review.mastered) {
        return 'Mastered. This sentence is done with review.';
    }

    if (review.days <= 0) {
        return 'Comes back for review today';
    }

    return review.days === 1
        ? 'Comes back for review tomorrow'
        : `Comes back for review in ${review.days} days`;
}
