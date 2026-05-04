<script setup lang="ts">
import { cn } from '@/lib/utils';

defineProps<{
  selected?: boolean;
}>();
</script>

<template>
  <tr
    :class="
      cn(
        'border-b border-border transition-colors',
        'hover:bg-gray-50/50',
        'data-[state=selected]:bg-primary-light',
        selected && 'bg-primary-light',
        $attrs.class as string
      )
    "
  >
    <slot />
  </tr>
</template>
