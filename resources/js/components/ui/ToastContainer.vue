<script setup lang="ts">
import { useToast } from '@/composables/ui/useToast';
import { X, CheckCircle, AlertCircle, AlertTriangle, Info } from 'lucide-vue-next';
import { TransitionGroup } from 'vue';

const { toasts, dismiss } = useToast();

const icons = {
    success: CheckCircle,
    error: AlertCircle,
    warning: AlertTriangle,
    info: Info,
} as const;

const styles = {
    success: 'border-green-200 bg-green-50 text-green-800 dark:border-green-800 dark:bg-green-950 dark:text-green-200',
    error: 'border-red-200 bg-red-50 text-red-800 dark:border-red-800 dark:bg-red-950 dark:text-red-200',
    warning: 'border-yellow-200 bg-yellow-50 text-yellow-800 dark:border-yellow-800 dark:bg-yellow-950 dark:text-yellow-200',
    info: 'border-blue-200 bg-blue-50 text-blue-800 dark:border-blue-800 dark:bg-blue-950 dark:text-blue-200',
} as const;

const iconStyles = {
    success: 'text-green-500 dark:text-green-400',
    error: 'text-red-500 dark:text-red-400',
    warning: 'text-yellow-500 dark:text-yellow-400',
    info: 'text-blue-500 dark:text-blue-400',
} as const;
</script>

<template>
    <Teleport to="body">
        <div
            class="pointer-events-none fixed inset-x-0 top-4 z-50 flex flex-col items-center gap-2 px-4"
            aria-live="polite"
        >
            <TransitionGroup
                enter-active-class="transition-all duration-300 ease-out"
                enter-from-class="translate-y-[-1rem] opacity-0"
                enter-to-class="translate-y-0 opacity-100"
                leave-active-class="transition-all duration-200 ease-in"
                leave-from-class="translate-y-0 opacity-100"
                leave-to-class="translate-y-[-1rem] opacity-0"
            >
                <div
                    v-for="toast in toasts"
                    :key="toast.id"
                    :class="['pointer-events-auto flex w-full max-w-sm items-start gap-3 rounded-lg border p-4 shadow-lg', styles[toast.type]]"
                    role="alert"
                >
                    <component
                        :is="icons[toast.type]"
                        :class="['mt-0.5 h-5 w-5 shrink-0', iconStyles[toast.type]]"
                    />
                    <div class="flex-1 space-y-1">
                        <p class="text-sm font-medium">{{ toast.title }}</p>
                        <p v-if="toast.message" class="text-xs opacity-80">{{ toast.message }}</p>
                    </div>
                    <button
                        type="button"
                        class="shrink-0 rounded-md p-1 opacity-60 hover:opacity-100 focus:outline-none focus:ring-2 focus:ring-current"
                        @click="dismiss(toast.id)"
                    >
                        <X class="h-4 w-4" />
                        <span class="sr-only">Tutup</span>
                    </button>
                </div>
            </TransitionGroup>
        </div>
    </Teleport>
</template>
