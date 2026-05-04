<script setup lang="ts">
import { cn } from '@/lib/utils';

defineProps<{
  class?: string;
}>();
</script>

<template>
  <p :class="cn('text-sm text-muted', $attrs.class as string)">
    <slot />
  </p>
</template>
