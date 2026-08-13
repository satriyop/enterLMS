<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Link } from '@inertiajs/vue3';
import { type LucideIcon } from 'lucide-vue-next';

interface Props {
    icon?: LucideIcon;
    title: string;
    description?: string;
    actionLabel?: string;
    actionHref?: string;
}

defineProps<Props>();
</script>

<template>
    <div class="flex flex-col items-center justify-center rounded-[var(--r-lg)] border border-dashed border-[var(--border-strong)] bg-surface px-6 py-16 text-center">
        <div v-if="icon" class="mb-4 flex h-[46px] w-[46px] items-center justify-center rounded-full bg-surface-2 text-subtle">
            <component :is="icon" class="h-5 w-5" />
        </div>
        <h3 class="text-editorial-h3 text-foreground">
            {{ title }}
        </h3>
        <p v-if="description" class="mt-2 max-w-md text-sm leading-relaxed text-muted-foreground">
            {{ description }}
        </p>
        <div class="mt-6">
            <slot name="action">
                <Link v-if="actionHref && actionLabel" :href="actionHref">
                    <Button>
                        {{ actionLabel }}
                    </Button>
                </Link>
            </slot>
        </div>
    </div>
</template>
