<script setup lang="ts">
import { cn } from '@/lib/utils';
import { TabsContent } from 'reka-ui';

defineProps<{
  value: string;
  class?: string;
}>();
</script>

<template>
  <TabsContent
    :value="value"
    :class="
      cn(
        'mt-4 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/50',
        $attrs.class as string
      )
    "
  >
    <slot />
  </TabsContent>
</template>
