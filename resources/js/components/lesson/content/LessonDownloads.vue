<script setup lang="ts">
import type { Media } from '@/types';
import { Download } from 'lucide-vue-next';

defineProps<{
    files: Media[];
}>();
</script>

<template>
    <section class="mb-6 rounded-md border border-border bg-surface px-4 py-3" aria-label="Unduh berkas">
        <p class="text-sm font-medium text-foreground">Unduh berkas</p>
        <p class="mt-1 text-sm text-muted-foreground">
            Simpan berkas ini, lalu jalankan berkas Python dengan Python 3 di komputermu.
        </p>
        <ul class="mt-3 flex flex-col gap-2">
            <li v-for="file in files" :key="file.id">
                <a
                    :href="file.url"
                    :download="file.file_name"
                    class="inline-flex items-center gap-2 text-sm font-medium text-foreground underline decoration-border underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current"
                >
                    <Download class="size-4 shrink-0" aria-hidden="true" />
                    {{ file.file_name }}
                </a>
            </li>
        </ul>
    </section>
</template>
