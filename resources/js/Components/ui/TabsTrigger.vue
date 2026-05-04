<script setup lang="ts">
import { cn } from '@/lib/utils';
import { TabsTrigger } from 'reka-ui';

defineProps<{
  value: string;
  disabled?: boolean;
}>();
</script>

<template>
  <TabsTrigger
    :value="value"
    :disabled="disabled"
    :class="
      cn(
        'inline-flex items-center justify-center whitespace-nowrap rounded-md px-3 py-1.5 text-sm font-medium transition-all',
        'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/50',
        'disabled:pointer-events-none disabled:opacity-50',
        'data-[state=active]:bg-white data-[state=active]:text-foreground data-[state=active]:shadow-sm',
        $attrs.class as string
      )
    "
  >
    <slot />
  </TabsTrigger>
</template>
