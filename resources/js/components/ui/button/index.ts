import { cva, type VariantProps } from 'class-variance-authority'

export { default as Button } from './Button.vue'

export const buttonVariants = cva(
  'inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-[6px] text-sm font-medium transition-all disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg:not([class*=\'size-\'])]:size-4 shrink-0 [&_svg]:shrink-0 outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive',
  {
    variants: {
      variant: {
        default:
          'bg-primary text-primary-foreground shadow-none hover:bg-[var(--primary-hover)]',
        destructive:
          'bg-destructive text-white shadow-none hover:opacity-90 focus-visible:ring-destructive/20 dark:focus-visible:ring-destructive/40 dark:bg-destructive/60',
        outline:
          'border border-[var(--border-strong)] bg-card text-foreground shadow-none hover:bg-surface-2 dark:bg-card dark:border-[var(--border-strong)] dark:hover:bg-surface-2',
        secondary:
          'bg-secondary text-secondary-foreground shadow-none hover:bg-surface-3',
        ghost:
          'text-muted-foreground hover:bg-surface-2 hover:text-foreground dark:hover:bg-surface-2',
        link: 'text-primary underline-offset-4 hover:underline',
      },
      size: {
        default: 'h-10 px-[1.05rem] py-2 has-[>svg]:px-3',
        sm: 'h-8 rounded-[6px] gap-1.5 px-3 text-[0.8rem] has-[>svg]:px-2.5',
        lg: 'h-12 rounded-[6px] px-6 text-[0.95rem] has-[>svg]:px-4',
        icon: 'size-9',
      },
    },
    defaultVariants: {
      variant: 'default',
      size: 'default',
    },
  },
)

export type ButtonVariants = VariantProps<typeof buttonVariants>
