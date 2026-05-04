<script setup lang="ts">
import { cn } from '@/lib/utils';

defineProps<{}>();
</script>

<template>
  <div
    :class="
      cn('animate-pulse rounded-lg bg-gray-100', $attrs.class as string)
    "
  />
</template>
