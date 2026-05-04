<script setup lang="ts">
import { cva, type VariantProps } from 'class-variance-authority';
import { cn } from '@/lib/utils';

const badgeVariants = cva(
  'inline-flex items-center rounded-md border px-2.5 py-0.5 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2',
  {
    variants: {
      variant: {
        default:
          'border-transparent bg-primary text-white shadow-sm',
        secondary:
          'border-transparent bg-gray-100 text-foreground-muted',
        success:
          'border-transparent bg-success-light text-success',
        danger:
          'border-transparent bg-danger-light text-danger',
        warning:
          'border-transparent bg-warning-light text-warning',
        info:
          'border-transparent bg-info-light text-info',
        outline:
          'border-border text-foreground',
      },
    },
    defaultVariants: {
      variant: 'default',
    },
  }
);

type BadgeVariants = VariantProps<typeof badgeVariants>;

defineProps<{
  variant?: BadgeVariants['variant'];
  class?: string;
}>();
</script>

<template>
  <div
    :class="cn(badgeVariants({ variant }), $attrs.class as string)"
  >
    <slot />
  </div>
</template>
