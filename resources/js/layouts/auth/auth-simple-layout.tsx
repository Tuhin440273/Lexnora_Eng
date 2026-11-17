import { Link } from '@inertiajs/react';
import AppLogoIcon from '@/components/app-logo-icon';
import { FloatingWords } from '@/components/floating-words';
import { home } from '@/routes';
import type { AuthLayoutProps } from '@/types';

export default function AuthSimpleLayout({
    children,
    title,
    description,
}: AuthLayoutProps) {
    return (
        <div className="relative flex min-h-svh flex-col items-center justify-center gap-6 overflow-hidden bg-background p-6 md:p-10">
            <FloatingWords />
            <div className="relative w-full max-w-sm animate-rise">
                <div className="flex flex-col gap-8">
                    <div className="flex flex-col items-center gap-4">
                        <Link
                            href={home()}
                            className="flex flex-col items-center gap-2 font-medium"
                        >
                            <div className="mb-1 flex size-14 items-center justify-center rounded-2xl bg-primary text-primary-foreground shadow-lg">
                                <AppLogoIcon className="size-8 fill-current" />
                            </div>
                            <span className="sr-only">{title}</span>
                        </Link>

                        <div className="space-y-2 text-center">
                            <h1 className="text-3xl font-semibold">{title}</h1>
                            <p className="text-center text-sm text-muted-foreground">
                                {description}
                            </p>
                        </div>
                    </div>
                    <div className="rounded-2xl border bg-card p-6 shadow-lg">
                        {children}
                    </div>
                </div>
            </div>
        </div>
    );
}
