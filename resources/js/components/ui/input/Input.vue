<script setup lang="ts">
import type { HTMLAttributes } from 'vue'
import { cn } from '@/lib/utils'
import { useVModel } from '@vueuse/core'

const props = defineProps<{
  defaultValue?: string | number
  modelValue?: string | number
  class?: HTMLAttributes['class']
}>()

const emits = defineEmits<{
  (e: 'update:modelValue', payload: string | number): void
}>()

const modelValue = useVModel(props, 'modelValue', emits, {
  passive: true,
  defaultValue: props.defaultValue,
})
</script>

<template>
  <input
    v-model="modelValue"
    data-slot="input"
    :class="cn(
      'flex h-[42px] w-full min-w-0 rounded-[var(--r-sm)] border border-border bg-surface px-[0.8rem] text-[0.9rem] outline-none shadow-none transition-[border-color,box-shadow] duration-150',
      'placeholder:text-subtle selection:bg-primary-soft selection:text-foreground',
      'file:inline-flex file:h-7 file:border-0 file:bg-transparent file:text-sm file:font-medium file:text-foreground',
      'disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50',
      'focus:border-primary focus:shadow-[0_0_0_3px_var(--primary-ring)]',
      'aria-invalid:border-danger aria-invalid:focus:shadow-[0_0_0_3px_var(--danger-soft)]',
      props.class,
    )"
  >
</template>
