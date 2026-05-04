<script setup lang="ts">
import { cn } from '@/lib/utils';

defineProps<{}>();
</script>

<template>
  <td
    :class="
      cn(
        'px-4 py-3 align-middle text-sm text-foreground',
        '[&:has([role=checkbox])]:pr-0',
        $attrs.class as string
      )
    "
  >
    <slot />
  </td>
</template>
