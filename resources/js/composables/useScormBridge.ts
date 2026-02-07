/**
 * Composable for managing the SCORM API bridge lifecycle.
 *
 * Sets up window.API (1.2) or window.API_1484_11 (2004) on the iframe's
 * parent window so SCORM content can find it via the standard discovery
 * algorithm (walk up window.parent chain).
 */

import { ref, onBeforeUnmount } from 'vue';
import { ScormApi12 } from '@/lib/scorm/ScormApi12';
import { ScormApi2004 } from '@/lib/scorm/ScormApi2004';

export interface ScormRuntimeUrls {
    initialize: string;
    get_value: string;
    set_value: string;
    commit: string;
    finish: string;
}

export interface UseScormBridgeOptions {
    version: string; // '1.2' or '2004'
    runtimeUrls: ScormRuntimeUrls;
    onComplete?: () => void;
}

export function useScormBridge(options: UseScormBridgeOptions) {
    const isInitialized = ref(false);
    const isCompleted = ref(false);

    let api12Instance: ScormApi12 | null = null;
    let api2004Instance: ScormApi2004 | null = null;

    const handleComplete = () => {
        isCompleted.value = true;
        options.onComplete?.();
    };

    /**
     * Install the SCORM API on window so the iframe content can find it.
     */
    function install(): void {
        if (options.version === '1.2') {
            api12Instance = new ScormApi12(options.runtimeUrls, handleComplete);
            (window as Record<string, unknown>).API = api12Instance;
        } else {
            api2004Instance = new ScormApi2004(options.runtimeUrls, handleComplete);
            (window as Record<string, unknown>).API_1484_11 = api2004Instance;
        }
        isInitialized.value = true;
    }

    /**
     * Remove the SCORM API from window.
     */
    function uninstall(): void {
        if (options.version === '1.2') {
            delete (window as Record<string, unknown>).API;
            api12Instance = null;
        } else {
            delete (window as Record<string, unknown>).API_1484_11;
            api2004Instance = null;
        }
        isInitialized.value = false;
    }

    // Auto-cleanup on component unmount
    onBeforeUnmount(() => {
        uninstall();
    });

    return {
        isInitialized,
        isCompleted,
        install,
        uninstall,
    };
}
