import type { VariantProps } from "class-variance-authority"
import { cva } from "class-variance-authority"

export { default as Badge } from "./Badge.vue"

/**
 * Tenang's `.badge` (ADR 007): a pill that pairs a `*-soft` background with
 * its full-strength ink. The tokens flip under `.dark` on their own, so no
 * variant here carries a `dark:` class.
 *
 * `default` is deliberately the *quiet* badge -- Tenang has no solid-fill
 * status pill. Reach for `primary` when a badge needs to carry brand weight.
 */
export const badgeVariants = cva(
  "inline-flex items-center justify-center gap-[0.3rem] rounded-pill border border-transparent px-[0.55rem] py-[0.16rem] text-[0.72rem] font-[550] tracking-[0.005em] w-fit whitespace-nowrap shrink-0 [&>svg]:size-3 [&>svg]:pointer-events-none focus-visible:ring-ring/50 focus-visible:ring-[3px] transition-[color,background-color,border-color] overflow-hidden",
  {
    variants: {
      variant: {
        default: "bg-surface-2 text-muted-foreground",
        secondary: "bg-surface-2 text-muted-foreground",
        primary: "bg-primary-soft text-primary",
        ok: "bg-ok-soft text-ok",
        warn: "bg-warn-soft text-warn",
        danger: "bg-danger-soft text-danger",
        destructive: "bg-danger-soft text-danger",
        info: "bg-info-soft text-info",
        gold: "bg-gold-soft text-gold",
        outline: "border-[var(--border-strong)] bg-transparent text-muted-foreground",
      },
    },
    defaultVariants: {
      variant: "default",
    },
  },
)
export type BadgeVariants = VariantProps<typeof badgeVariants>
