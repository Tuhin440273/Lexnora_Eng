import { describe, expect, it } from 'vitest';
import { formatMinutes, formatBytes, formatDuration } from './format';
import {
    describeNextReview,
    formatPercent,
    formatStudyTime,
    wordsOf,
} from './learn';

describe('formatStudyTime', () => {
    it('uses seconds, minutes and hours', () => {
        expect(formatStudyTime(45)).toBe('45 s');
        expect(formatStudyTime(600)).toBe('10 min');
        expect(formatStudyTime(3600)).toBe('1 h');
        expect(formatStudyTime(5400)).toBe('1 h 30 min');
    });
});

describe('formatPercent', () => {
    it('rounds to one decimal and handles null', () => {
        expect(formatPercent(null)).toBe('-');
        expect(formatPercent(92.345)).toBe('92.3%');
        expect(formatPercent(100)).toBe('100%');
    });
});

describe('wordsOf', () => {
    it('splits a sentence into unique lowercase words', () => {
        expect(wordsOf("I don't like tea, but I like Tea.")).toEqual([
            'i',
            "don't",
            'like',
            'tea',
            'but',
        ]);
    });

    it('keeps hyphenated words together', () => {
        expect(wordsOf('Where is the check-in desk?')).toEqual([
            'where',
            'is',
            'the',
            'check-in',
            'desk',
        ]);
    });

    it('returns nothing for punctuation only', () => {
        expect(wordsOf('... ?!')).toEqual([]);
    });
});

describe('admin formatters', () => {
    it('formats sizes, durations and minutes', () => {
        expect(formatBytes(null)).toBe('-');
        expect(formatBytes(2048)).toBe('2.0 KB');
        expect(formatDuration(65000)).toBe('1:05');
        expect(formatMinutes(90)).toBe('1 h 30 min');
        expect(formatMinutes(0)).toBe('-');
    });
});

describe('describeNextReview', () => {
    it('says nothing for words and phrases, which are not scheduled', () => {
        expect(describeNextReview(null)).toBeNull();
    });

    it('speaks of today, tomorrow and later days', () => {
        expect(describeNextReview({ days: 0, mastered: false })).toBe(
            'Comes back for review today',
        );
        expect(describeNextReview({ days: 1, mastered: false })).toBe(
            'Comes back for review tomorrow',
        );
        expect(describeNextReview({ days: 7, mastered: false })).toBe(
            'Comes back for review in 7 days',
        );
    });

    it('says a mastered sentence is done with review', () => {
        expect(describeNextReview({ days: 7, mastered: true })).toBe(
            'Mastered. This sentence is done with review.',
        );
    });
});
