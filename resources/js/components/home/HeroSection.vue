<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { router } from '@inertiajs/vue3';
import { Search } from 'lucide-vue-next';
import { ref } from 'vue';

interface Props {
    title?: string;
    subtitle?: string;
    backgroundImage?: string;
}

withDefaults(defineProps<Props>(), {
    title: 'Belajar Tanpa Batas',
    subtitle: 'Tingkatkan keterampilan Anda dengan ribuan kursus online dari instruktur terbaik.',
});

const searchQuery = ref('');

const handleSearch = () => {
    if (searchQuery.value.trim()) {
        router.get('/courses', { search: searchQuery.value });
    }
};
</script>

<template>
    <section class="relative overflow-hidden border-b bg-surface">
        <div class="relative mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8 lg:py-24">
            <div class="mx-auto max-w-3xl text-center">
                <p class="text-eyebrow mb-4">Academy</p>
                <h1 class="text-display text-foreground">
                    {{ title }}
                </h1>
                <p class="text-lead mx-auto mt-5 max-w-[52ch]">
                    {{ subtitle }}
                </p>

                <form
                    @submit.prevent="handleSearch"
                    class="mx-auto mt-10 flex max-w-xl gap-2"
                >
                    <div class="relative flex-1">
                        <Search
                            class="absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-muted-foreground"
                        />
                        <Input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Cari kursus yang Anda inginkan..."
                            class="h-12 bg-surface pl-12 text-base shadow-sm-editorial"
                        />
                    </div>
                    <Button
                        type="submit"
                        size="lg"
                        class="h-12 px-6"
                    >
                        Cari
                    </Button>
                </form>

                <div class="mt-8 flex flex-wrap items-center justify-center gap-3 text-tiny">
                    <span>Populer:</span>
                    <button
                        type="button"
                        class="rounded-full border border-border px-3 py-1 text-foreground transition-colors hover:border-primary hover:text-primary"
                        @click="searchQuery = 'Agen AI'; handleSearch()"
                    >
                        Agen AI
                    </button>
                    <button
                        type="button"
                        class="rounded-full border border-border px-3 py-1 text-foreground transition-colors hover:border-primary hover:text-primary"
                        @click="searchQuery = 'OpenClaw'; handleSearch()"
                    >
                        OpenClaw
                    </button>
                    <button
                        type="button"
                        class="rounded-full border border-border px-3 py-1 text-foreground transition-colors hover:border-primary hover:text-primary"
                        @click="searchQuery = 'Agen AI'; handleSearch()"
                    >
                        Agen AI
                    </button>
                </div>
            </div>
        </div>
    </section>
</template>
