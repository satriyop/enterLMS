import { cva, type VariantProps } from 'class-variance-authority'

export { default as Card } from './Card.vue'
export { default as CardAction } from './CardAction.vue'
export { default as CardContent } from './CardContent.vue'
export { default as CardDescription } from './CardDescription.vue'
export { default as CardFooter } from './CardFooter.vue'
export { default as CardHeader } from './CardHeader.vue'
export { default as CardTitle } from './CardTitle.vue'

/**
 * Tenang builds depth from stacked surfaces rather than shadows (ADR 007), so
 * a Card sitting inside another Card should step down to `quiet` instead of
 * repeating `default` -- two identical surfaces read as one flat plane.
 *
 * - `default` — `--surface`, hairline border. The standard panel.
 * - `quiet`   — `--surface-2`, no border. For a panel nested inside one.
 * - `flat`    — no surface at all. Groups content without drawing a box.
 *
 * `hoverable` adds Tenang's lift; use it only when the whole card is a link.
 */
export const cardVariants = cva(
  'flex flex-col gap-6 rounded-[var(--r)] py-6',
  {
    variants: {
      variant: {
        default: 'bg-surface border border-border',
        quiet: 'bg-surface-2 border border-transparent',
        flat: 'bg-transparent border-none',
      },
      hoverable: {
        true: 'transition-[border-color,box-shadow,transform] duration-[180ms] hover:border-[var(--border-strong)] hover:shadow-editorial hover:-translate-y-0.5',
        false: '',
      },
    },
    defaultVariants: {
      variant: 'default',
      hoverable: false,
    },
  },
)

export type CardVariants = VariantProps<typeof cardVariants>
