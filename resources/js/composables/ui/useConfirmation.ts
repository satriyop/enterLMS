// =============================================================================
// useConfirmation Composable
// Promise-based confirmation dialog state management
// =============================================================================

import { ref, readonly, type DeepReadonly, type Ref } from 'vue';

// =============================================================================
// Types
// =============================================================================

interface ConfirmOptions {
    /** Dialog title */
    title?: string;
    /** Confirmation message */
    message: string;
    /** Confirm button label */
    confirmLabel?: string;
    /** Cancel button label */
    cancelLabel?: string;
    /** Whether this is a destructive action (styles button red) */
    destructive?: boolean;
}

interface UseConfirmationReturn {
    /** Whether the confirmation dialog is open */
    readonly isOpen: DeepReadonly<Ref<boolean>>;
    /** Dialog title */
    readonly title: DeepReadonly<Ref<string>>;
    /** Confirmation message */
    readonly message: DeepReadonly<Ref<string>>;
    /** Confirm button label */
    readonly confirmLabel: DeepReadonly<Ref<string>>;
    /** Cancel button label */
    readonly cancelLabel: DeepReadonly<Ref<string>>;
    /** Whether this is a destructive action */
    readonly isDestructive: DeepReadonly<Ref<boolean>>;
    /** Show confirmation dialog and wait for user response */
    confirm: (options: ConfirmOptions) => Promise<boolean>;
    /** Handle user clicking confirm */
    handleConfirm: () => void;
    /** Handle user clicking cancel */
    handleCancel: () => void;
}

// =============================================================================
// Composable
// =============================================================================

export function useConfirmation(): UseConfirmationReturn {
    // State
    const isOpen = ref(false);
    const title = ref('Konfirmasi');
    const message = ref('');
    const confirmLabel = ref('Ya');
    const cancelLabel = ref('Batal');
    const isDestructive = ref(false);

    // Promise resolver
    let resolvePromise: ((value: boolean) => void) | null = null;

    /**
     * Show confirmation dialog and wait for user response
     */
    function confirm(options: ConfirmOptions): Promise<boolean> {
        // Set dialog content
        title.value = options.title ?? 'Konfirmasi';
        message.value = options.message;
        confirmLabel.value = options.confirmLabel ?? 'Ya';
        cancelLabel.value = options.cancelLabel ?? 'Batal';
        isDestructive.value = options.destructive ?? false;

        // Open dialog
        isOpen.value = true;

        // Return promise that resolves when user responds
        return new Promise((resolve) => {
            resolvePromise = resolve;
        });
    }

    /**
     * Handle user clicking confirm
     */
    function handleConfirm(): void {
        isOpen.value = false;
        resolvePromise?.(true);
        resolvePromise = null;
    }

    /**
     * Handle user clicking cancel
     */
    function handleCancel(): void {
        isOpen.value = false;
        resolvePromise?.(false);
        resolvePromise = null;
    }

    return {
        isOpen: readonly(isOpen),
        title: readonly(title),
        message: readonly(message),
        confirmLabel: readonly(confirmLabel),
        cancelLabel: readonly(cancelLabel),
        isDestructive: readonly(isDestructive),
        confirm,
        handleConfirm,
        handleCancel,
    };
}
