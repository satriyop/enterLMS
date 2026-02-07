<script setup lang="ts">
// =============================================================================
// SCORM Player Page
// Full-page SCORM content viewer with runtime API bridge
// =============================================================================

import { Head, router } from '@inertiajs/vue3';
import ScormPlayerEmbed from '@/components/lesson/content/ScormPlayerEmbed.vue';
import type { ScormRuntimeUrls } from '@/composables/useScormBridge';

interface Props {
    lesson: {
        id: number;
        title: string;
    };
    package: {
        id: number;
        version: string;
        entry_point: string;
        content_url: string;
    };
    runtime_urls: ScormRuntimeUrls;
}

const props = defineProps<Props>();

function handleCompleted(): void {
    // Optionally navigate back or show success message
    router.reload({ only: ['lesson'] });
}
</script>

<template>
    <Head :title="`SCORM: ${lesson.title}`" />

    <div class="flex h-screen flex-col bg-gray-100 dark:bg-gray-900">
        <!-- Header bar -->
        <header class="flex items-center justify-between border-b border-gray-200 bg-white px-4 py-3 dark:border-gray-700 dark:bg-gray-800">
            <div class="flex items-center gap-3">
                <span class="rounded bg-indigo-100 px-2 py-0.5 text-xs font-medium text-indigo-700 dark:bg-indigo-900 dark:text-indigo-300">
                    SCORM {{ package.version }}
                </span>
                <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                    {{ lesson.title }}
                </h1>
            </div>
        </header>

        <!-- Player -->
        <main class="flex-1 overflow-hidden">
            <ScormPlayerEmbed
                :content-url="package.content_url"
                :version="package.version"
                :runtime-urls="runtime_urls"
                :lesson-title="lesson.title"
                @completed="handleCompleted"
            />
        </main>
    </div>
</template>
