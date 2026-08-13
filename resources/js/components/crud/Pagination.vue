<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight } from 'lucide-vue-next';

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface Props {
    links: PaginationLink[];
    currentPage: number;
    lastPage: number;
    from?: number;
    to?: number;
    total?: number;
}

const props = defineProps<Props>();

const prevLink = props.links.find((l) => l.label.includes('Previous') || l.label.includes('&laquo;'));
const nextLink = props.links.find((l) => l.label.includes('Next') || l.label.includes('&raquo;'));
const pageLinks = props.links.filter(
    (l) => !l.label.includes('Previous') && !l.label.includes('Next') && !l.label.includes('&laquo;') && !l.label.includes('&raquo;')
);
</script>

<template>
    <div class="flex flex-col items-center justify-between gap-4 sm:flex-row">
        <p v-if="from && to && total" class="text-sm text-muted-foreground">
            Menampilkan <span class="font-medium tabular-nums text-foreground">{{ from }}</span> -
            <span class="font-medium tabular-nums text-foreground">{{ to }}</span> dari
            <span class="font-medium tabular-nums text-foreground">{{ total }}</span> hasil
        </p>

        <div class="flex items-center gap-1">
            <Link
                v-if="prevLink?.url"
                :href="prevLink.url"
                class="inline-flex h-9 w-9 items-center justify-center rounded-[var(--r-sm)] border border-border bg-surface text-muted-foreground transition-colors hover:border-[var(--border-strong)] hover:text-foreground"
            >
                <ChevronLeft class="h-4 w-4" />
            </Link>
            <span
                v-else
                class="inline-flex h-9 w-9 cursor-not-allowed items-center justify-center rounded-[var(--r-sm)] border border-transparent bg-surface-2 text-subtle opacity-60"
            >
                <ChevronLeft class="h-4 w-4" />
            </span>

            <template v-for="link in pageLinks" :key="link.label">
                <Link
                    v-if="link.url"
                    :href="link.url"
                    class="inline-flex h-9 min-w-9 items-center justify-center rounded-[var(--r-sm)] border px-3 text-sm font-medium tabular-nums transition-colors"
                    :class="
                        link.active
                            ? 'border-foreground bg-foreground text-background'
                            : 'border-border bg-surface text-muted-foreground hover:border-[var(--border-strong)] hover:text-foreground'
                    "
                >
                    {{ link.label }}
                </Link>
                <span
                    v-else
                    class="inline-flex h-9 min-w-9 items-center justify-center px-2 text-sm text-subtle"
                >
                    {{ link.label }}
                </span>
            </template>

            <Link
                v-if="nextLink?.url"
                :href="nextLink.url"
                class="inline-flex h-9 w-9 items-center justify-center rounded-[var(--r-sm)] border border-border bg-surface text-muted-foreground transition-colors hover:border-[var(--border-strong)] hover:text-foreground"
            >
                <ChevronRight class="h-4 w-4" />
            </Link>
            <span
                v-else
                class="inline-flex h-9 w-9 cursor-not-allowed items-center justify-center rounded-[var(--r-sm)] border border-transparent bg-surface-2 text-subtle opacity-60"
            >
                <ChevronRight class="h-4 w-4" />
            </span>
        </div>
    </div>
</template>
