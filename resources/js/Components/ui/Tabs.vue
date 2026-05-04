<script setup lang="ts">
import { cn } from '@/lib/utils';
import { TabsRoot, TabsList, TabsTrigger, TabsContent } from 'reka-ui';

defineProps<{
  defaultValue?: string;
  modelValue?: string;
}>();

defineEmits<{
  'update:modelValue': [value: string];
}>();
</script>

<template>
  <TabsRoot
    :default-value="defaultValue"
    :model-value="modelValue"
    @update:model-value="$emit('update:modelValue', $event as string)"
  >
    <slot />
  </TabsRoot>
</template>
