<script setup lang="ts">
import { cn } from '@/lib/utils';
import { computed } from 'vue';

const props = defineProps<{
  value?: number;
  max?: number;
  indicatorClass?: string;
  size?: 'sm' | 'default' | 'lg';
}>();

const percentage = computed(() => {
  const val = props.value ?? 0;
  const maxVal = props.max ?? 100;
  return Math.min(100, Math.max(0, (val / maxVal) * 100));
});

const sizeClasses = {
  sm: 'h-1.5',
  default: 'h-2.5',
  lg: 'h-4',
};
</script>

<template>
  <div
    role="progressbar"
    :aria-valuenow="value"
    :aria-valuemin="0"
    :aria-valuemax="max ?? 100"
    :class="
      cn(
        'w-full overflow-hidden rounded-full bg-gray-100',
        sizeClasses[size ?? 'default'],
        $attrs.class as string
      )
    "
  >
    <div
      :class="cn('h-full rounded-full bg-primary transition-all duration-500 ease-out', indicatorClass)"
      :style="{ width: `${percentage}%` }"
    />
  </div>
</template>
