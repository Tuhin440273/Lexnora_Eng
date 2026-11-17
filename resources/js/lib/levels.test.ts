import { describe, expect, it } from 'vitest';
import { levelAccent } from './levels';

describe('levelAccent', () => {
    it('returns the key for levels that have an accent', () => {
        expect(levelAccent('starter')).toBe('starter');
        expect(levelAccent('a1')).toBe('a1');
        expect(levelAccent('b2')).toBe('b2');
    });

    it('returns undefined for levels without an accent or bad input', () => {
        expect(levelAccent('c1')).toBeUndefined();
        expect(levelAccent('A1')).toBeUndefined();
        expect(levelAccent('')).toBeUndefined();
        expect(levelAccent(null)).toBeUndefined();
        expect(levelAccent(undefined)).toBeUndefined();
    });
});
