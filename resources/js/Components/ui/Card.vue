<script setup lang="ts">
import { cn } from '@/lib/utils';

defineProps<{}>();
</script>

<template>
  <div
    :class="
      cn(
        'rounded-xl border border-border bg-white shadow-xs',
        $attrs.class as string
      )
    "
  >
    <slot />
  </div>
</template>
