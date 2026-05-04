<script setup lang="ts">
import { cn } from '@/lib/utils';
import { computed } from 'vue';

const props = defineProps<{
  modelValue?: string | number;
  type?: string;
  placeholder?: string;
  disabled?: boolean;
  readonly?: boolean;
  error?: string;
  class?: string;
  id?: string;
  autofocus?: boolean;
  autocomplete?: string;
  min?: string | number;
  max?: string | number;
  step?: string | number;
}>();

const emit = defineEmits<{
  'update:modelValue': [value: string | number];
}>();

const inputValue = computed({
  get: () => props.modelValue ?? '',
  set: (val) => emit('update:modelValue', val),
});
</script>

<template>
  <input
    :id="id"
    :type="type ?? 'text'"
    v-model="inputValue"
    :placeholder="placeholder"
    :disabled="disabled"
    :readonly="readonly"
    :autofocus="autofocus"
    :autocomplete="autocomplete"
    :min="min"
    :max="max"
    :step="step"
    :class="
      cn(
        'flex h-10 w-full rounded-lg border border-border bg-white px-3 py-2 text-sm text-foreground shadow-xs transition-colors',
        'placeholder:text-muted-foreground',
        'focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20',
        'disabled:cursor-not-allowed disabled:opacity-50 disabled:bg-gray-50',
        'file:border-0 file:bg-transparent file:text-sm file:font-medium',
        error && 'border-danger focus:border-danger focus:ring-danger/20',
        $attrs.class as string
      )
    "
  />
</template>
