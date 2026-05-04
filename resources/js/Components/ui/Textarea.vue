<script setup lang="ts">
import { cn } from '@/lib/utils';
import { computed } from 'vue';

const props = defineProps<{
  modelValue?: string;
  placeholder?: string;
  disabled?: boolean;
  readonly?: boolean;
  error?: string;
  id?: string;
  rows?: number;
  maxlength?: number;
}>();

const emit = defineEmits<{
  'update:modelValue': [value: string];
}>();

const inputValue = computed({
  get: () => props.modelValue ?? '',
  set: (val) => emit('update:modelValue', val),
});
</script>

<template>
  <textarea
    :id="id"
    v-model="inputValue"
    :placeholder="placeholder"
    :disabled="disabled"
    :readonly="readonly"
    :rows="rows ?? 4"
    :maxlength="maxlength"
    :class="
      cn(
        'flex min-h-[80px] w-full rounded-lg border border-border bg-white px-3 py-2 text-sm text-foreground shadow-xs transition-colors',
        'placeholder:text-muted-foreground',
        'focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20',
        'disabled:cursor-not-allowed disabled:opacity-50 disabled:bg-gray-50',
        'resize-y',
        error && 'border-danger focus:border-danger focus:ring-danger/20',
        $attrs.class as string
      )
    "
  />
</template>
