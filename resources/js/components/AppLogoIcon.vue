<script setup lang="ts">
/**
 * Enter mark: a doorway that reads as E.
 * Uses currentColor so it sits on pine (navbar) or ink (auth) without a bitmap.
 */
defineOptions({
    inheritAttrs: false,
});
</script>

<template>
    <svg
        xmlns="http://www.w3.org/2000/svg"
        viewBox="0 0 32 32"
        fill="currentColor"
        role="img"
        aria-label="EnterLMS"
        v-bind="$attrs"
    >
        <title>EnterLMS</title>
        <rect x="4" y="4" width="5" height="24" rx="1.25" />
        <rect x="4" y="4" width="24" height="5" rx="1.25" />
        <rect x="4" y="13.5" width="15" height="5" rx="1.25" />
        <rect x="4" y="23" width="24" height="5" rx="1.25" />
    </svg>
</template>
