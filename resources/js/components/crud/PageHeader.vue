<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowLeft } from 'lucide-vue-next';

interface Props {
    title: string;
    description?: string;
    /** Small uppercase kicker above the title — Tenang's `.eyebrow`. */
    eyebrow?: string;
    backHref?: string;
    backLabel?: string;
}

withDefaults(defineProps<Props>(), {
    backLabel: 'Kembali',
});
</script>

<template>
    <!-- No bottom margin here: `.section-hairline` supplies its own. -->
    <div>
        <Link
            v-if="backHref"
            :href="backHref"
            class="mb-4 inline-flex items-center gap-2 text-sm text-muted-foreground transition-colors hover:text-foreground"
        >
            <ArrowLeft class="h-4 w-4" />
            {{ backLabel }}
        </Link>

        <!--
            Tenang separates a page head from its body with a hairline rather
            than whitespace alone, and aligns the actions to the title's
            baseline instead of its centre.
        -->
        <div class="section-hairline">
            <div>
                <p v-if="eyebrow" class="text-eyebrow mb-2">{{ eyebrow }}</p>
                <h1 class="text-editorial-h1 text-foreground">
                    {{ title }}
                </h1>
                <p v-if="description" class="text-lead mt-2 max-w-[60ch]">
                    {{ description }}
                </p>
            </div>
            <div class="flex items-center gap-3">
                <slot name="actions" />
            </div>
        </div>
    </div>
</template>
