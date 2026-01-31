// =============================================================================
// useSearch Composable
// URL-based search with debouncing for Inertia applications
// =============================================================================

import { ref, watch, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { debounce } from '@/lib/utils';
import { DEBOUNCE } from '@/lib/constants';

// =============================================================================
// Types
// =============================================================================

interface UseSearchOptions {
    /** Target URL for search requests (default: window.location.pathname) */
    url?: string | (() => string);
    /** URL parameter name for search query */
    paramName?: string;
    /** Debounce delay in milliseconds */
    debounceMs?: number;
    /** Minimum characters before triggering search */
    minLength?: number;
    /** Initial search value */
    initial?: string;
    /** Only preserve these props during search */
    only?: string[];
    /** Additional query parameters to include with every search */
    extraParams?: () => Record<string, string | undefined>;
}

// =============================================================================
// Composable
// =============================================================================

export function useSearch(options: UseSearchOptions = {}) {
    const {
        url,
        paramName = 'search',
        debounceMs = DEBOUNCE.search,
        minLength = 1,
        initial = '',
        only,
        extraParams,
    } = options;

    // State
    const query = ref(initial);
    const isSearching = ref(false);

    // Computed
    const hasQuery = computed(() => query.value.length >= minLength);

    /**
     * Resolve the target URL
     */
    function resolveUrl(): string {
        if (typeof url === 'function') return url();
        return url ?? window.location.pathname;
    }

    /**
     * Perform the actual search request
     */
    const performSearch = debounce((searchQuery: string) => {
        isSearching.value = true;

        const data: Record<string, string | undefined> = {
            [paramName]: searchQuery || undefined, // undefined removes param
            ...extraParams?.(),
        };

        router.get(resolveUrl(), data, {
            preserveState: true,
            preserveScroll: true,
            only,
            onFinish: () => {
                isSearching.value = false;
            },
        });
    }, debounceMs);

    // Watch for query changes
    watch(query, (newQuery) => {
        // Only search if empty (clear) or meets minimum length
        if (newQuery.length === 0 || newQuery.length >= minLength) {
            performSearch(newQuery);
        }
    });

    /**
     * Clear the search query
     */
    function clear(): void {
        query.value = '';
    }

    /**
     * Set the search query programmatically
     */
    function search(searchQuery: string): void {
        query.value = searchQuery;
    }

    return {
        // State
        query,
        isSearching,

        // Computed
        hasQuery,

        // Methods
        clear,
        search,
    };
}
