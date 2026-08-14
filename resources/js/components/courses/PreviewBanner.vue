<script setup lang="ts">
// =============================================================================
// PreviewBanner Component
// Banner showing preview mode status with enroll button
// =============================================================================

import { Button } from '@/components/ui/button';
import { Eye } from 'lucide-vue-next';

// =============================================================================
// Types
// =============================================================================

interface Props {
    isEnrolled: boolean;
    isEnrolling: boolean;
}

// =============================================================================
// Component Setup
// =============================================================================

defineProps<Props>();

const emit = defineEmits<{
    enroll: [];
}>();
</script>

<template>
    <div class="mb-6 rounded-lg border border-border bg-info-soft p-4">
        <div class="flex items-center gap-3">
            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-info-soft">
                <Eye class="h-5 w-5 text-info" />
            </div>
            <div class="flex-1">
                <p class="font-medium text-info">Mode Preview</p>
                <p class="text-sm text-info">
                    Anda sedang melihat preview materi. Daftar ke kursus untuk akses penuh.
                </p>
            </div>
            <Button
                v-if="!isEnrolled"
                size="sm"
                :disabled="isEnrolling"
                @click="emit('enroll')"
            >
                {{ isEnrolling ? 'Mendaftar...' : 'Daftar Kursus' }}
            </Button>
        </div>
    </div>
</template>
