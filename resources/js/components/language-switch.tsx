import { router } from '@inertiajs/react';
import LanguageController from '@/actions/App/Modules/Learning/Http/Controllers/LanguageController';
import { LOCALES, useT } from '@/lib/i18n';
import type { Locale } from '@/lib/i18n';
import { cn } from '@/lib/utils';

/** English | বাংলা. Saves the choice (cookie, and the profile when signed in) and reloads the page. */
export function LanguageSwitch({ className }: { className?: string }) {
    const t = useT();

    const choose = (locale: Locale) => {
        if (locale !== t.locale) {
            router.post(
                LanguageController.url(),
                { locale },
                { preserveScroll: true, preserveState: false },
            );
        }
    };

    return (
        <nav
            aria-label={t('Language')}
            className={cn(
                'inline-flex items-center rounded-full border bg-card p-0.5 text-sm',
                className,
            )}
        >
            {LOCALES.map((item) => (
                <button
                    key={item.value}
                    type="button"
                    lang={item.value}
                    aria-pressed={t.locale === item.value}
                    onClick={() => choose(item.value)}
                    className={cn(
                        'rounded-full px-3 py-1 font-medium transition-colors focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none',
                        t.locale === item.value
                            ? 'bg-primary text-primary-foreground'
                            : 'text-muted-foreground hover:text-foreground',
                    )}
                >
                    {item.label}
                </button>
            ))}
        </nav>
    );
}
