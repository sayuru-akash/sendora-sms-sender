<script setup lang="ts">
import { cn } from '@/lib/utils';

defineProps<{
  name: string;
  color?: string;
}>();
</script>

<template>
  <span
    :class="
      cn(
        'inline-flex items-center gap-1 rounded-md px-2 py-0.5 text-xs font-medium border',
        color ?? 'bg-blue-50 text-blue-700 border-blue-200',
        $attrs.class as string
      )
    "
  >
    {{ name }}
  </span>
</template>
