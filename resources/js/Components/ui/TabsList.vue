<script setup lang="ts">
import { cn } from '@/lib/utils';
import { TabsList } from 'reka-ui';

defineProps<{
  class?: string;
}>();
</script>

<template>
  <TabsList
    :class="
      cn(
        'inline-flex h-10 items-center justify-center rounded-lg bg-gray-100 p-1 text-muted',
        $attrs.class as string
      )
    "
  >
    <slot />
  </TabsList>
</template>
