<script setup lang="ts">
// =============================================================================
// ScormPlayerEmbed Component
// Renders a SCORM package inside an iframe with the JS API bridge
// =============================================================================

import { ref, onMounted } from 'vue';
import { useScormBridge } from '@/composables/useScormBridge';
import type { ScormRuntimeUrls } from '@/composables/useScormBridge';

interface Props {
    /** URL to the SCORM content entry point */
    contentUrl: string;
    /** SCORM version: '1.2' or '2004' */
    version: string;
    /** Runtime API URLs for the backend */
    runtimeUrls: ScormRuntimeUrls;
    /** Lesson title for display */
    lessonTitle?: string;
}

const props = withDefaults(defineProps<Props>(), {
    lessonTitle: 'SCORM Content',
});

const emit = defineEmits<{
    /** Emitted when SCORM content signals completion */
    completed: [];
}>();

const iframeRef = ref<HTMLIFrameElement | null>(null);
const isLoading = ref(true);

const { isInitialized, isCompleted, install } = useScormBridge({
    version: props.version,
    runtimeUrls: props.runtimeUrls,
    onComplete: () => {
        emit('completed');
    },
});

onMounted(() => {
    // Install the API bridge before the iframe loads
    install();
});

function handleIframeLoad(): void {
    isLoading.value = false;
}
</script>

<template>
    <div class="relative flex h-full w-full flex-col">
        <!-- Loading overlay -->
        <div
            v-if="isLoading"
            class="absolute inset-0 z-10 flex items-center justify-center bg-white dark:bg-gray-900"
        >
            <div class="text-center">
                <div
                    class="mx-auto mb-3 h-10 w-10 animate-spin rounded-full border-4 border-indigo-200 border-t-indigo-600"
                />
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Memuat konten SCORM...
                </p>
            </div>
        </div>

        <!-- Completion badge -->
        <div
            v-if="isCompleted"
            class="bg-green-50 px-4 py-2 text-center text-sm font-medium text-green-700 dark:bg-green-900/30 dark:text-green-300"
        >
            Konten SCORM telah selesai
        </div>

        <!-- SCORM Content iframe -->
        <iframe
            ref="iframeRef"
            :src="contentUrl"
            :title="lessonTitle"
            class="h-full min-h-[500px] w-full flex-1 border-0"
            :class="{ 'opacity-0': isLoading }"
            allow="autoplay; fullscreen"
            sandbox="allow-scripts allow-same-origin allow-forms allow-popups"
            @load="handleIframeLoad"
        />
    </div>
</template>
