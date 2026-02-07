/**
 * SCORM 1.2 JavaScript API Bridge
 *
 * Implements the window.API object that SCORM 1.2 content expects.
 * Routes all calls through AJAX to the backend ScormRuntimeController.
 */

import type { ScormRuntimeUrls } from '@/composables/useScormBridge';
import { CMI_DEFAULTS_12, READ_ONLY_12 } from './cmi-defaults';

export class ScormApi12 {
    private initialized = false;
    private finished = false;
    private lastError = '0';
    private cache: Record<string, string> = { ...CMI_DEFAULTS_12 };
    private csrfToken: string;

    constructor(
        private urls: ScormRuntimeUrls,
        private onComplete?: () => void,
    ) {
        this.csrfToken = document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '';
    }

    LMSInitialize(_param: string = ''): string {
        if (this.initialized) {
            this.lastError = '101'; // Already initialized
            return 'false';
        }

        // Synchronous call to initialize
        this.sendSync(this.urls.initialize, {});
        this.initialized = true;
        this.lastError = '0';
        return 'true';
    }

    LMSGetValue(element: string): string {
        if (!this.initialized || this.finished) {
            this.lastError = '301'; // Not initialized
            return '';
        }

        // Check cache first
        if (element in this.cache) {
            this.lastError = '0';
            return this.cache[element];
        }

        // Fetch from server
        const result = this.sendSync(this.urls.get_value, { element });
        if (result && result.value !== undefined) {
            this.cache[element] = result.value;
            this.lastError = '0';
            return result.value;
        }

        this.lastError = '0';
        return '';
    }

    LMSSetValue(element: string, value: string): string {
        if (!this.initialized || this.finished) {
            this.lastError = '301';
            return 'false';
        }

        if (READ_ONLY_12.has(element)) {
            this.lastError = '403'; // Read-only element
            return 'false';
        }

        // Update local cache
        this.cache[element] = value;

        // Send to server asynchronously
        this.sendAsync(this.urls.set_value, { element, value });

        this.lastError = '0';
        return 'true';
    }

    LMSCommit(_param: string = ''): string {
        if (!this.initialized || this.finished) {
            this.lastError = '301';
            return 'false';
        }

        this.sendAsync(this.urls.commit, {});
        this.lastError = '0';
        return 'true';
    }

    LMSFinish(_param: string = ''): string {
        if (!this.initialized || this.finished) {
            this.lastError = '301';
            return 'false';
        }

        this.sendSync(this.urls.finish, {});
        this.finished = true;
        this.initialized = false;
        this.lastError = '0';

        this.onComplete?.();
        return 'true';
    }

    LMSGetLastError(): string {
        return this.lastError;
    }

    LMSGetErrorString(errorCode: string): string {
        const errors: Record<string, string> = {
            '0': 'No Error',
            '101': 'General Exception',
            '201': 'Invalid argument error',
            '202': 'Element cannot have children',
            '203': 'Element not an array. Cannot have count',
            '301': 'Not initialized',
            '401': 'Not implemented error',
            '402': 'Invalid set value, element is a keyword',
            '403': 'Element is read only',
            '404': 'Element is write only',
            '405': 'Incorrect Data Type',
        };
        return errors[errorCode] || 'Unknown Error';
    }

    LMSGetDiagnostic(errorCode: string): string {
        return this.LMSGetErrorString(errorCode);
    }

    private sendSync(url: string, data: Record<string, string>): Record<string, string> | null {
        try {
            const xhr = new XMLHttpRequest();
            xhr.open('POST', url, false); // Synchronous
            xhr.setRequestHeader('Content-Type', 'application/json');
            xhr.setRequestHeader('X-CSRF-TOKEN', this.csrfToken);
            xhr.setRequestHeader('Accept', 'application/json');
            xhr.send(JSON.stringify(data));

            if (xhr.status === 200) {
                return JSON.parse(xhr.responseText);
            }
        } catch {
            // Silently fail — SCORM content should check GetLastError
        }
        return null;
    }

    private sendAsync(url: string, data: Record<string, string>): void {
        fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': this.csrfToken,
                Accept: 'application/json',
            },
            body: JSON.stringify(data),
        }).catch(() => {
            // Silently fail for async calls
        });
    }
}
