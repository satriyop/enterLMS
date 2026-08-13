<script setup lang="ts">
import type { HTMLAttributes } from 'vue'
import { cn } from '@/lib/utils'
import { type CardVariants, cardVariants } from '.'

const props = defineProps<{
  variant?: CardVariants['variant']
  hoverable?: CardVariants['hoverable']
  class?: HTMLAttributes['class']
}>()
</script>

<template>
  <div
    data-slot="card"
    :class="
      cn(
        'text-card-foreground',
        cardVariants({ variant: props.variant, hoverable: props.hoverable }),
        props.class,
      )
    "
  >
    <slot />
  </div>
</template>
