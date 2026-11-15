import { describe, expect, it } from 'vitest';
import { guideWords, matchGuide, normalizeWord } from './guide';

describe('normalizeWord', () => {
    it('ignores case, punctuation and curly quotes', () => {
        expect(normalizeWord('Coffee,')).toBe('coffee');
        expect(normalizeWord('Don\u2019t')).toBe("don't");
        expect(normalizeWord('check-in')).toBe('check-in');
        expect(normalizeWord('?!')).toBe('');
    });
});

describe('guideWords', () => {
    it('splits on any whitespace', () => {
        expect(guideWords('  I  would\nlike tea. ')).toEqual([
            'I',
            'would',
            'like',
            'tea.',
        ]);
        expect(guideWords('')).toEqual([]);
    });
});

describe('matchGuide', () => {
    const guide = guideWords('I would like a cup of tea, please.');

    it('marks nothing before the learner types', () => {
        expect(matchGuide(guide, '')).toEqual(guide.map(() => false));
    });

    it('marks words as they are typed, ignoring case and punctuation', () => {
        expect(matchGuide(guide, 'i WOULD like')).toEqual([
            true,
            true,
            true,
            false,
            false,
            false,
            false,
            false,
        ]);
    });

    it('does not mark a word that is still being typed', () => {
        expect(matchGuide(guide, 'I wou')).toEqual([
            true,
            false,
            false,
            false,
            false,
            false,
            false,
            false,
        ]);
    });

    it('keeps going after a mistake', () => {
        expect(matchGuide(guide, 'I wuld like a cup')).toEqual([
            true,
            false,
            true,
            true,
            true,
            false,
            false,
            false,
        ]);
    });

    it('copes with an extra word', () => {
        expect(matchGuide(guide, 'I really would like')).toEqual([
            true,
            true,
            true,
            false,
            false,
            false,
            false,
            false,
        ]);
    });

    it('marks everything when the whole sentence is typed', () => {
        expect(
            matchGuide(guide, 'I would like a cup of tea please').every(
                Boolean,
            ),
        ).toBe(true);
    });

    it('treats a lone symbol in the guide as done', () => {
        expect(matchGuide(['Yes', '-', 'please'], 'yes please')).toEqual([
            true,
            true,
            true,
        ]);
    });
});
