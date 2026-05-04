<script setup lang="ts">
import { cn } from '@/lib/utils';
import { DropdownMenuLabel } from 'reka-ui';

defineProps<{
  inset?: boolean;
  class?: string;
}>();
</script>

<template>
  <DropdownMenuLabel
    :class="
      cn(
        'px-2 py-1.5 text-xs font-semibold text-muted uppercase tracking-wider',
        inset && 'pl-8',
        $attrs.class as string
      )
    "
  >
    <slot />
  </DropdownMenuLabel>
</template>
