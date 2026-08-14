<script setup lang="ts">
// =============================================================================
// FlashMessages Component
// Displays success and error flash messages from Inertia page props
// =============================================================================

import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

// =============================================================================
// Component Setup
// =============================================================================

const page = usePage();

const flash = computed(() => page.props.flash as { success?: string; error?: string; warning?: string } | undefined);
</script>

<template>
    <div v-if="flash?.success || flash?.error || flash?.warning" class="space-y-2">
        <div v-if="flash?.success" class="rounded-md bg-ok-soft p-4">
            <div class="flex">
                <div class="ml-3">
                    <p class="text-sm font-medium text-ok">
                        {{ flash.success }}
                    </p>
                </div>
            </div>
        </div>
        <div v-if="flash?.error" class="rounded-md bg-danger-soft p-4">
            <div class="flex">
                <div class="ml-3">
                    <p class="text-sm font-medium text-danger">
                        {{ flash.error }}
                    </p>
                </div>
            </div>
        </div>
        <div v-if="flash?.warning" class="rounded-md bg-warn-soft p-4">
            <div class="flex">
                <div class="ml-3">
                    <p class="text-sm font-medium text-warn">
                        {{ flash.warning }}
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>
