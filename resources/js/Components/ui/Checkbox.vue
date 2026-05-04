<script setup lang="ts">
import { cn } from '@/lib/utils';

const props = defineProps<{
  modelValue?: boolean;
  disabled?: boolean;
  id?: string;
  class?: string;
}>();

const emit = defineEmits<{
  'update:modelValue': [value: boolean];
}>();
</script>

<template>
  <input
    :id="id"
    type="checkbox"
    :checked="modelValue"
    :disabled="disabled"
    @change="emit('update:modelValue', ($event.target as HTMLInputElement).checked)"
    :class="
      cn(
        'h-4 w-4 shrink-0 rounded border border-border bg-white transition-colors',
        'checked:bg-primary checked:border-primary',
        'focus:outline-none focus:ring-2 focus:ring-primary/20 focus:ring-offset-2',
        'disabled:cursor-not-allowed disabled:opacity-50',
        'cursor-pointer',
        $attrs.class as string
      )
    "
  />
</template>
