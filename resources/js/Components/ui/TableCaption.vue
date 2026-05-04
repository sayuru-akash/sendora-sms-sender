<script setup lang="ts">
import { cn } from '@/lib/utils';

defineProps<{
  class?: string;
}>();
</script>

<template>
  <caption
    :class="cn('mt-4 text-sm text-muted', $attrs.class as string)"
  >
    <slot />
  </caption>
</template>
