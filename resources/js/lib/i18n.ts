import { usePage } from '@inertiajs/react';
import { bn } from '@/lib/i18n/bn';

/**
 * The interface language. English text is its own key: a phrase with no entry
 * in the Bangla dictionary simply shows in English, so nothing breaks when a
 * translation is missing.
 */
export type Locale = 'en' | 'bn';

export const LOCALES: { value: Locale; label: string }[] = [
    { value: 'en', label: 'English' },
    { value: 'bn', label: 'বাংলা' },
];

type Vars = Record<string, string | number>;

const dictionaries: Record<Locale, Record<string, string>> = { en: {}, bn };

export function translate(locale: Locale, text: string, vars?: Vars): string {
    const phrase = dictionaries[locale][text] ?? text;

    return vars
        ? phrase.replace(/\{(\w+)\}/g, (match, name: string) =>
              String(vars[name] ?? match),
          )
        : phrase;
}

const PLAIN_COUNT = new Set(['time', 'minute']);

/** "3 lessons" in English, "3টি পাঠ" in Bangla (the noun is looked up like any phrase). */
export function countOf(
    locale: Locale,
    count: number,
    one: string,
    other = `${one}s`,
): string {
    if (locale === 'bn') {
        // "3টি পাঠ", but "3 বার" and "5 মিনিট": some words take no counting word
        return `${count}${PLAIN_COUNT.has(one) ? '' : 'টি'} ${translate(locale, one)}`;
    }

    return `${count} ${count === 1 ? one : other}`;
}

/** Returns t("English text", { name: 'x' }) for the language of the current page. */
export function useT() {
    const { locale } = usePage().props;

    return Object.assign(
        (text: string, vars?: Vars) => translate(locale, text, vars),
        {
            n: (count: number, one: string, other?: string) =>
                countOf(locale, count, one, other),
            locale,
        },
    );
}
