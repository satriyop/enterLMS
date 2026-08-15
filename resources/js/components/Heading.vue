<script setup lang="ts">
interface Props {
    title: string;
    description?: string;
}

defineProps<Props>();
</script>

<template>
    <div class="mb-8 space-y-2">
        <h2 class="text-editorial-h1 text-foreground">{{ title }}</h2>
        <p v-if="description" class="text-lead max-w-[60ch]">
            {{ description }}
        </p>
    </div>
</template>
