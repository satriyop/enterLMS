<script setup lang="ts">
interface Props {
    title: string;
    description?: string;
    collapsible?: boolean;
}

withDefaults(defineProps<Props>(), {
    collapsible: false,
});
</script>

<template>
    <div class="overflow-hidden rounded-[var(--r)] border border-border bg-surface">
        <!-- The header sits on the next surface up, so the hairline reads as a fold. -->
        <div class="border-b border-border bg-surface-2 px-6 py-4">
            <h3 class="text-editorial-h3 text-foreground">{{ title }}</h3>
            <p v-if="description" class="mt-1 text-sm text-muted-foreground">
                {{ description }}
            </p>
        </div>
        <div class="p-6">
            <slot />
        </div>
    </div>
</template>
