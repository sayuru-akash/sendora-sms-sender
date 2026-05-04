<script setup lang="ts">
import { cva, type VariantProps } from 'class-variance-authority';
import { cn } from '@/lib/utils';
import { AlertCircle, CheckCircle2, Info, AlertTriangle } from 'lucide-vue-next';
import { computed } from 'vue';

const alertVariants = cva(
  'relative w-full rounded-lg border p-4 flex items-start gap-3',
  {
    variants: {
      variant: {
        default: 'bg-white border-border text-foreground',
        info: 'border-info/30 bg-info-light text-info',
        success: 'border-success/30 bg-success-light text-success',
        warning: 'border-warning/30 bg-warning-light text-warning',
        danger: 'border-danger/30 bg-danger-light text-danger',
      },
    },
    defaultVariants: {
      variant: 'default',
    },
  }
);

type AlertVariants = VariantProps<typeof alertVariants>;

const props = defineProps<{
  variant?: AlertVariants['variant'];
  title?: string;
}>();

const iconMap = {
  default: Info,
  info: Info,
  success: CheckCircle2,
  warning: AlertTriangle,
  danger: AlertCircle,
};

const icon = computed(() => iconMap[props.variant ?? 'default']);
</script>

<template>
  <div
    role="alert"
    :class="cn(alertVariants({ variant }), $attrs.class as string)"
  >
    <component :is="icon" class="h-5 w-5 shrink-0 mt-0.5" />
    <div class="flex-1">
      <h5 v-if="title" class="mb-1 font-medium text-sm">{{ title }}</h5>
      <div class="text-sm">
        <slot />
      </div>
    </div>
  </div>
</template>
