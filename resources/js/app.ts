import '../css/app.css';

import { createInertiaApp } from '@inertiajs/vue3';
import { initializeTheme } from './composables/useAppearance';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    pages: './pages',
    progress: {
        color: '#4B5563',
    },
});

// Client-only: theme is also bootstrapped via inline script in app.blade.php
if (typeof window !== 'undefined') {
    initializeTheme();
}
