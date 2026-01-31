// =============================================================================
// useViewMode Composable
// Persistent grid/list view mode toggle with localStorage
// =============================================================================

import { ref, type Ref } from 'vue';
import { STORAGE_KEYS } from '@/lib/constants';

type ViewMode = 'grid' | 'list';

interface UseViewModeOptions {
    /** Storage key suffix for persistence (e.g., 'courses', 'assessments') */
    key: string;
    /** Default view mode */
    defaultMode?: ViewMode;
}

interface UseViewModeReturn {
    /** Current view mode */
    viewMode: Ref<ViewMode>;
    /** Whether current mode is grid */
    isGrid: () => boolean;
    /** Whether current mode is list */
    isList: () => boolean;
    /** Set view mode */
    setMode: (mode: ViewMode) => void;
    /** CSS classes for grid layout */
    gridClasses: string;
    /** CSS classes for list layout */
    listClasses: string;
    /** Get container classes based on current mode */
    containerClasses: () => string;
}

export function useViewMode(options: UseViewModeOptions): UseViewModeReturn {
    const { key, defaultMode = 'grid' } = options;

    const storageKey = `${STORAGE_KEYS.theme}-viewmode-${key}`;

    // Restore from localStorage or use default
    const saved = localStorage.getItem(storageKey) as ViewMode | null;
    const viewMode = ref<ViewMode>(saved ?? defaultMode);

    function setMode(mode: ViewMode): void {
        viewMode.value = mode;
        localStorage.setItem(storageKey, mode);
    }

    const gridClasses = 'grid gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4';
    const listClasses = 'flex flex-col gap-4';

    return {
        viewMode,
        isGrid: () => viewMode.value === 'grid',
        isList: () => viewMode.value === 'list',
        setMode,
        gridClasses,
        listClasses,
        containerClasses: () => viewMode.value === 'grid' ? gridClasses : listClasses,
    };
}
