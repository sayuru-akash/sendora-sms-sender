<script setup lang="ts">
import { cn } from '@/lib/utils';

defineProps<{}>();
</script>

<template>
  <h3
    :class="
      cn('text-lg font-semibold leading-none tracking-tight text-foreground', $attrs.class as string)
    "
  >
    <slot />
  </h3>
</template>
