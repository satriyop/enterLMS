<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';

interface Props {
    status: number;
}

const props = defineProps<Props>();

const titles: Record<number, string> = {
    403: 'Akses Ditolak',
    404: 'Halaman Tidak Ditemukan',
    500: 'Kesalahan Server',
    503: 'Layanan Tidak Tersedia',
};

const descriptions: Record<number, string> = {
    403: 'Maaf, Anda tidak memiliki izin untuk mengakses halaman ini.',
    404: 'Maaf, halaman yang Anda cari tidak dapat ditemukan.',
    500: 'Maaf, terjadi kesalahan pada server kami.',
    503: 'Maaf, kami sedang melakukan pemeliharaan. Silakan coba lagi nanti.',
};

const title = computed(() => titles[props.status] ?? 'Terjadi Kesalahan');
const description = computed(() => descriptions[props.status] ?? 'Terjadi kesalahan yang tidak terduga.');

const goBack = () => window.history.back();
</script>

<template>
    <Head :title="`${status} - ${title}`" />

    <div class="flex min-h-svh flex-col items-center justify-center bg-background px-4">
        <div class="flex flex-col items-center gap-6 text-center">
            <AppLogoIcon class="size-12 fill-current text-muted-foreground" />

            <div class="space-y-2">
                <p class="text-7xl font-bold tracking-tight text-foreground">{{ status }}</p>
                <h1 class="text-2xl font-semibold text-foreground">{{ title }}</h1>
                <p class="max-w-md text-muted-foreground">{{ description }}</p>
            </div>

            <div class="flex gap-3">
                <a
                    href="/"
                    class="inline-flex items-center rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground shadow-sm hover:bg-primary/90"
                >
                    Kembali ke Beranda
                </a>
                <button
                    type="button"
                    class="inline-flex items-center rounded-md border border-input bg-background px-4 py-2 text-sm font-medium text-foreground shadow-sm hover:bg-accent"
                    @click="goBack"
                >
                    Kembali
                </button>
            </div>
        </div>
    </div>
</template>
