<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { form as verifyForm } from '@/routes/certificates/verify';
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

interface Props {
    appName?: string;
    description?: string;
}

withDefaults(defineProps<Props>(), {
    appName: 'EnterLMS',
    description: 'Academy untuk menjalankan dan membangun keluarga produk AI.',
});

const page = usePage();
const user = computed(() => page.props.auth?.user);
const year = new Date().getFullYear();
</script>

<template>
    <footer class="border-t bg-surface">
        <div class="mx-auto flex max-w-7xl flex-col gap-6 px-4 py-10 sm:px-6 lg:flex-row lg:items-center lg:justify-between lg:px-8">
            <div class="max-w-sm space-y-3">
                <Link href="/" class="inline-flex items-center gap-2">
                    <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-primary">
                        <AppLogoIcon class="h-5 w-5 fill-current text-primary-foreground" />
                    </div>
                    <span class="font-display text-xl font-normal tracking-tight text-foreground">
                        {{ appName }}
                    </span>
                </Link>
                <p class="text-sm text-muted-foreground">
                    {{ description }}
                </p>
            </div>

            <nav class="flex flex-wrap gap-x-6 gap-y-2 text-sm" aria-label="Tautan footer">
                <Link
                    v-if="user"
                    href="/courses"
                    class="text-muted-foreground transition-colors hover:text-foreground"
                >
                    Katalog
                </Link>
                <Link
                    :href="verifyForm().url"
                    class="text-muted-foreground transition-colors hover:text-foreground"
                >
                    Verifikasi sertifikat
                </Link>
                <Link
                    v-if="!user"
                    href="/login"
                    class="text-muted-foreground transition-colors hover:text-foreground"
                >
                    Masuk
                </Link>
            </nav>
        </div>

        <div class="border-t">
            <p class="mx-auto max-w-7xl px-4 py-4 text-tiny sm:px-6 lg:px-8">
                © {{ year }} {{ appName }}
            </p>
        </div>
    </footer>
</template>
