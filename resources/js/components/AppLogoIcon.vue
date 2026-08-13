<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

interface Props {
    className?: HTMLAttributes['class'];
}

defineProps<Props>();
</script>

<template>
    <img
        src="/storage/enterlms-logo-min.svg"
        alt="EnterLMS Logo"
        :class="className"
        v-bind="$attrs"
    />
</template>
