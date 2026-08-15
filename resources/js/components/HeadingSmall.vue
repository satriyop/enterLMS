<script setup lang="ts">
interface Props {
    title: string;
    description?: string;
}

defineProps<Props>();
</script>

<template>
    <header>
        <h3 class="text-editorial-h3 text-foreground">{{ title }}</h3>
        <p v-if="description" class="mt-1 text-sm text-muted-foreground">
            {{ description }}
        </p>
    </header>
</template>
