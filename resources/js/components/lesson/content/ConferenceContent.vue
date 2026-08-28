<script setup lang="ts">
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import { ExternalLink, Video as VideoCall } from 'lucide-vue-next';
import ContentPlaceholder from './ContentPlaceholder.vue';

type ConferenceType = 'zoom' | 'google_meet' | 'other';

interface Props {
    conferenceUrl?: string | null;
    conferenceType?: ConferenceType | null;
}

const props = defineProps<Props>();

const platformLabel = computed(() => {
    switch (props.conferenceType) {
        case 'zoom':
            return 'Zoom';
        case 'google_meet':
            return 'Google Meet';
        case 'other':
            return 'Konferensi';
        default:
            return 'Konferensi';
    }
});
</script>

<template>
    <ContentPlaceholder
        v-if="!conferenceUrl"
        :icon="VideoCall"
        message="Tautan konferensi belum tersedia"
    />
    <div
        v-else
        class="w-full max-w-lg rounded-xl border border-border bg-surface p-8"
    >
        <div class="flex flex-col items-center gap-4 text-center">
            <div class="flex h-14 w-14 items-center justify-center rounded-xl bg-ok-soft text-ok">
                <VideoCall class="h-7 w-7" />
            </div>
            <div class="space-y-2">
                <p class="font-heading text-lg text-foreground">{{ platformLabel }}</p>
                <p class="text-sm text-muted-foreground">
                    Sesi live dengan manusia. Pelajaran ini bukan konsol agen.
                </p>
            </div>
            <Button as-child>
                <a
                    :href="conferenceUrl"
                    target="_blank"
                    rel="noopener noreferrer"
                >
                    <ExternalLink class="h-4 w-4" />
                    Buka tautan konferensi
                </a>
            </Button>
            <p class="break-all text-xs text-muted-foreground">{{ conferenceUrl }}</p>
        </div>
    </div>
</template>
