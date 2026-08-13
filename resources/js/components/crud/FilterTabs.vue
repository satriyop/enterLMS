<script setup lang="ts">
interface Tab {
    value: string;
    label: string;
    count?: number;
}

interface Props {
    tabs: Tab[];
    modelValue: string;
}

const props = defineProps<Props>();
const emit = defineEmits<{
    'update:modelValue': [value: string];
}>();

const selectTab = (value: string) => {
    emit('update:modelValue', value);
};
</script>

<template>
    <!--
        Tenang's `.chip` row: free-standing pills on the page background, not
        segments inside a track. The selected chip inverts to ink-on-paper
        rather than lifting a white tab out of a grey well.
    -->
    <div class="flex flex-wrap gap-2">
        <button
            v-for="tab in tabs"
            :key="tab.value"
            type="button"
            :aria-pressed="modelValue === tab.value"
            class="inline-flex items-center gap-2 rounded-pill border px-[0.8rem] py-[0.38rem] text-[0.82rem] transition-all duration-150"
            :class="
                modelValue === tab.value
                    ? 'border-foreground bg-foreground font-[550] text-background'
                    : 'border-border bg-surface text-muted-foreground hover:border-[var(--border-strong)] hover:text-foreground'
            "
            @click="selectTab(tab.value)"
        >
            {{ tab.label }}
            <span
                v-if="tab.count !== undefined"
                class="text-[0.72rem] tabular-nums"
                :class="modelValue === tab.value ? 'text-background/70' : 'text-subtle'"
            >
                {{ tab.count }}
            </span>
        </button>
    </div>
</template>
