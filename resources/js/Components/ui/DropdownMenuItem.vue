<script setup lang="ts">
import { cn } from '@/lib/utils';
import { DropdownMenuItem } from 'reka-ui';

defineProps<{
  inset?: boolean;
  destructive?: boolean;
  disabled?: boolean;
  class?: string;
}>();

defineEmits<{
  select: [event: Event];
}>();
</script>

<template>
  <DropdownMenuItem
    :disabled="disabled"
    @select="$emit('select', $event)"
    :class="
      cn(
        'relative flex cursor-pointer select-none items-center gap-2 rounded-md px-2 py-1.5 text-sm outline-none transition-colors',
        'focus:bg-gray-100 focus:text-foreground',
        'disabled:pointer-events-none disabled:opacity-50',
        destructive && 'text-danger focus:bg-danger-light focus:text-danger',
        inset && 'pl-8',
        $attrs.class as string
      )
    "
  >
    <slot />
  </DropdownMenuItem>
</template>
