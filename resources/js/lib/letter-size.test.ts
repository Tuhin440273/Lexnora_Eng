import { describe, expect, it } from 'vitest';
import { parseLetterSize, stepLetterSize } from './letter-size';

describe('parseLetterSize', () => {
    it('accepts the known sizes', () => {
        expect(parseLetterSize('normal')).toBe('normal');
        expect(parseLetterSize('large')).toBe('large');
        expect(parseLetterSize('larger')).toBe('larger');
    });

    it('falls back to normal for anything else', () => {
        expect(parseLetterSize(null)).toBe('normal');
        expect(parseLetterSize(undefined)).toBe('normal');
        expect(parseLetterSize('')).toBe('normal');
        expect(parseLetterSize('huge')).toBe('normal');
    });
});

describe('stepLetterSize', () => {
    it('moves one step at a time', () => {
        expect(stepLetterSize('normal', 1)).toBe('large');
        expect(stepLetterSize('large', 1)).toBe('larger');
        expect(stepLetterSize('larger', -1)).toBe('large');
    });

    it('stops at both ends', () => {
        expect(stepLetterSize('larger', 1)).toBe('larger');
        expect(stepLetterSize('normal', -1)).toBe('normal');
    });
});
