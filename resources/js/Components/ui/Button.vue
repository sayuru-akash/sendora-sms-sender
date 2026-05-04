<script setup lang="ts">
import { cva, type VariantProps } from 'class-variance-authority';
import { cn } from '@/lib/utils';
import { Loader2 } from 'lucide-vue-next';

const buttonVariants = cva(
  'inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-lg text-sm font-medium transition-all duration-150 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/50 focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 cursor-pointer select-none',
  {
    variants: {
      variant: {
        default:
          'bg-primary text-white shadow-sm hover:bg-primary-hover active:bg-primary-700',
        destructive:
          'bg-danger text-white shadow-sm hover:bg-red-600 active:bg-red-700',
        outline:
          'border border-border bg-white text-foreground shadow-sm hover:bg-gray-50 active:bg-gray-100',
        secondary:
          'bg-gray-100 text-foreground shadow-sm hover:bg-gray-200 active:bg-gray-300',
        ghost:
          'text-foreground-muted hover:bg-gray-100 hover:text-foreground active:bg-gray-200',
        link: 'text-primary underline-offset-4 hover:underline',
      },
      size: {
        default: 'h-10 px-4 py-2',
        sm: 'h-8 rounded-md px-3 text-xs',
        lg: 'h-11 rounded-lg px-6 text-base',
        xl: 'h-12 rounded-lg px-8 text-base',
        icon: 'h-10 w-10',
        'icon-sm': 'h-8 w-8',
      },
    },
    defaultVariants: {
      variant: 'default',
      size: 'default',
    },
  }
);

type ButtonVariants = VariantProps<typeof buttonVariants>;

defineProps<{
  variant?: ButtonVariants['variant'];
  size?: ButtonVariants['size'];
  loading?: boolean;
  disabled?: boolean;
  type?: 'button' | 'submit' | 'reset';
}>();
</script>

<template>
  <button
    :type="type ?? 'button'"
    :disabled="disabled || loading"
    :class="cn(buttonVariants({ variant, size }), $attrs.class as string)"
  >
    <Loader2 v-if="loading" class="h-4 w-4 animate-spin" />
    <slot />
  </button>
</template>
