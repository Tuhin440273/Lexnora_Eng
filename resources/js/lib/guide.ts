export function normalizeWord(word: string): string {
    return word
        .toLowerCase()
        .replace(/[\u2019]/g, "'")
        .replace(/[^\p{L}\p{N}'-]+/gu, '')
        .replace(/^['-]+|['-]+$/g, '');
}

export function guideWords(text: string): string[] {
    return text.split(/\s+/).filter((word) => word !== '');
}

/**
 * For every word of the guide, whether the learner has typed it correctly.
 * Words are matched in order. A wrong or extra word in between is skipped
 * (up to two), so one mistake does not switch off the rest of the guide.
 */
export function matchGuide(guide: string[], typed: string): boolean[] {
    const typedWords = typed
        .split(/\s+/)
        .map(normalizeWord)
        .filter((word) => word !== '');

    let pointer = 0;

    return guide.map((word) => {
        const target = normalizeWord(word);

        if (target === '') {
            return true;
        }

        for (let skip = 0; skip <= 2; skip++) {
            if (typedWords[pointer + skip] === target) {
                pointer += skip + 1;

                return true;
            }
        }

        return false;
    });
}
