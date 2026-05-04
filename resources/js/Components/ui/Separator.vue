<script setup lang="ts">
import { cn } from '@/lib/utils';

defineProps<{
  orientation?: 'horizontal' | 'vertical';
}>();
</script>

<template>
  <div
    role="separator"
    :class="
      cn(
        'shrink-0 bg-border',
        orientation === 'vertical' ? 'w-px h-full' : 'h-px w-full',
        $attrs.class as string
      )
    "
  />
</template>
