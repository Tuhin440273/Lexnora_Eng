export type CoverScene =
    | 'sunrise'
    | 'shopping'
    | 'health'
    | 'work'
    | 'travel'
    | 'book';

/** Picks the picture for a course cover from its category name. */
export function coverSceneFor(category: string | null | undefined): CoverScene {
    const name = (category ?? '').toLowerCase();

    if (/shop|money|market|price/.test(name)) {
        return 'shopping';
    }

    if (/health|doctor|medic|body/.test(name)) {
        return 'health';
    }

    if (/work|business|office|job|career/.test(name)) {
        return 'work';
    }

    if (/travel|trip|journey|city|transport/.test(name)) {
        return 'travel';
    }

    if (/daily|life|routine|home|family/.test(name)) {
        return 'sunrise';
    }

    return 'book';
}
