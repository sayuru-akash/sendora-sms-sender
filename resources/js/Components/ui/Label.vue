<script setup lang="ts">
import { cn } from '@/lib/utils';

defineProps<{
  required?: boolean;
}>();
</script>

<template>
  <label
    :class="
      cn(
        'text-sm font-medium text-foreground leading-none',
        'peer-disabled:cursor-not-allowed peer-disabled:opacity-70',
        $attrs.class as string
      )
    "
  >
    <slot />
    <span v-if="required" class="ml-0.5 text-danger">*</span>
  </label>
</template>
