/**
 * SCORM 2004 JavaScript API Bridge
 *
 * Implements the window.API_1484_11 object that SCORM 2004 content expects.
 * Routes all calls through AJAX to the backend ScormRuntimeController.
 */

import type { ScormRuntimeUrls } from '@/composables/useScormBridge';
import { CMI_DEFAULTS_2004, READ_ONLY_2004 } from './cmi-defaults';

export class ScormApi2004 {
    private initialized = false;
    private terminated = false;
    private lastError = 0;
    private cache: Record<string, string> = { ...CMI_DEFAULTS_2004 };
    private csrfToken: string;

    constructor(
        private urls: ScormRuntimeUrls,
        private onComplete?: () => void,
    ) {
        this.csrfToken = document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '';
    }

    Initialize(_param: string = ''): string {
        if (this.initialized) {
            this.lastError = 103; // Already initialized
            return 'false';
        }

        if (this.terminated) {
            this.lastError = 104; // Content instance terminated
            return 'false';
        }

        this.sendSync(this.urls.initialize, {});
        this.initialized = true;
        this.lastError = 0;
        return 'true';
    }

    GetValue(element: string): string {
        if (!this.initialized || this.terminated) {
            this.lastError = 122; // Store not initialized
            return '';
        }

        if (element in this.cache) {
            this.lastError = 0;
            return this.cache[element];
        }

        const result = this.sendSync(this.urls.get_value, { element });
        if (result && result.value !== undefined) {
            this.cache[element] = result.value;
            this.lastError = 0;
            return result.value;
        }

        this.lastError = 0;
        return '';
    }

    SetValue(element: string, value: string): string {
        if (!this.initialized || this.terminated) {
            this.lastError = 132;
            return 'false';
        }

        if (READ_ONLY_2004.has(element)) {
            this.lastError = 404; // Data model element is read only
            return 'false';
        }

        this.cache[element] = value;
        this.sendAsync(this.urls.set_value, { element, value });

        this.lastError = 0;
        return 'true';
    }

    Commit(_param: string = ''): string {
        if (!this.initialized || this.terminated) {
            this.lastError = 142;
            return 'false';
        }

        this.sendAsync(this.urls.commit, {});
        this.lastError = 0;
        return 'true';
    }

    Terminate(_param: string = ''): string {
        if (!this.initialized || this.terminated) {
            this.lastError = 112;
            return 'false';
        }

        this.sendSync(this.urls.finish, {});
        this.terminated = true;
        this.initialized = false;
        this.lastError = 0;

        this.onComplete?.();
        return 'true';
    }

    GetLastError(): string {
        return String(this.lastError);
    }

    GetErrorString(errorCode: string): string {
        const errors: Record<string, string> = {
            '0': 'No Error',
            '101': 'General Exception',
            '102': 'General Initialization Failure',
            '103': 'Already Initialized',
            '104': 'Content Instance Terminated',
            '111': 'General Termination Failure',
            '112': 'Termination Before Initialization',
            '113': 'Termination After Termination',
            '122': 'Retrieve Data Before Initialization',
            '123': 'Retrieve Data After Termination',
            '132': 'Store Data Before Initialization',
            '133': 'Store Data After Termination',
            '142': 'Commit Before Initialization',
            '143': 'Commit After Termination',
            '201': 'General Argument Error',
            '301': 'General Get Failure',
            '351': 'General Set Failure',
            '391': 'General Commit Failure',
            '401': 'Undefined Data Model Element',
            '402': 'Unimplemented Data Model Element',
            '403': 'Data Model Element Value Not Initialized',
            '404': 'Data Model Element Is Read Only',
            '405': 'Data Model Element Is Write Only',
            '406': 'Data Model Element Type Mismatch',
            '407': 'Data Model Element Value Out Of Range',
            '408': 'Data Model Dependency Not Established',
        };
        return errors[errorCode] || 'Unknown Error Code';
    }

    GetDiagnostic(errorCode: string): string {
        return this.GetErrorString(errorCode);
    }

    private sendSync(url: string, data: Record<string, string>): Record<string, string> | null {
        try {
            const xhr = new XMLHttpRequest();
            xhr.open('POST', url, false);
            xhr.setRequestHeader('Content-Type', 'application/json');
            xhr.setRequestHeader('X-CSRF-TOKEN', this.csrfToken);
            xhr.setRequestHeader('Accept', 'application/json');
            xhr.send(JSON.stringify(data));

            if (xhr.status === 200) {
                return JSON.parse(xhr.responseText);
            }
        } catch {
            // Silently fail
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
            // Silently fail
        });
    }
}
