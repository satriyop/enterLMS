import '../css/app.css';

import { createInertiaApp } from '@inertiajs/vue3';
import { initializeTheme } from './composables/useAppearance';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

/**
 * Inertia's progress bar wants a literal colour, so the pine token has to be
 * resolved here rather than referenced. `app.css` is imported above, so the
 * token is always present in a browser; falling back to `undefined` lets
 * Inertia use its own default in the impossible case that it is not.
 *
 * Read once at boot -- the bar is transient enough not to follow a later
 * theme switch.
 */
function progressColor(): string | undefined {
    if (typeof document === 'undefined') {
        return undefined;
    }

    return getComputedStyle(document.documentElement).getPropertyValue('--primary').trim() || undefined;
}

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    pages: './pages',
    progress: {
        color: progressColor(),
    },
});

// Client-only: theme is also bootstrapped via inline script in app.blade.php
if (typeof window !== 'undefined') {
    initializeTheme();
}
