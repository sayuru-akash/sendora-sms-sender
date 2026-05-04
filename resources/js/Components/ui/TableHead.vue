<script setup lang="ts">
import { cn } from '@/lib/utils';

defineProps<{
  class?: string;
}>();
</script>

<template>
  <th
    :class="
      cn(
        'h-10 px-4 text-left align-middle font-medium text-muted text-xs uppercase tracking-wider',
        '[&:has([role=checkbox])]:pr-0',
        $attrs.class as string
      )
    "
  >
    <slot />
  </th>
</template>
