<script setup lang="ts">
import { Search, X } from 'lucide-vue-next';

interface Props {
    modelValue: string;
    placeholder?: string;
}

const props = withDefaults(defineProps<Props>(), {
    placeholder: 'Cari...',
});

const emit = defineEmits<{
    'update:modelValue': [value: string];
}>();

const clearSearch = () => {
    emit('update:modelValue', '');
};
</script>

<template>
    <div class="relative">
        <Search class="absolute left-[0.8rem] top-1/2 h-4 w-4 -translate-y-1/2 text-subtle" />
        <input
            :value="modelValue"
            type="text"
            :placeholder="placeholder"
            class="h-[42px] w-full rounded-[var(--r-sm)] border border-border bg-surface pl-[2.4rem] pr-10 text-[0.9rem] outline-none transition-[border-color,box-shadow] duration-150 placeholder:text-subtle focus:border-primary focus:shadow-[0_0_0_3px_var(--primary-ring)]"
            @input="emit('update:modelValue', ($event.target as HTMLInputElement).value)"
        />
        <button
            v-if="modelValue"
            type="button"
            class="absolute right-3 top-1/2 -translate-y-1/2 rounded-pill p-1 text-subtle transition-colors hover:bg-surface-2 hover:text-foreground"
            @click="clearSearch"
        >
            <X class="h-4 w-4" />
        </button>
    </div>
</template>
