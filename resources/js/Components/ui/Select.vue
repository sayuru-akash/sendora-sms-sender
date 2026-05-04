<script setup lang="ts">
import { cn } from '@/lib/utils';
import { computed } from 'vue';

interface SelectOption {
  label: string;
  value: string | number;
  disabled?: boolean;
}

const props = defineProps<{
  modelValue?: string | number;
  options: SelectOption[];
  placeholder?: string;
  disabled?: boolean;
  error?: string;
  id?: string;
}>();

const selectBgUrl = `bg-[url(\"data:image/svg+xml;charset=utf-8,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%2224%22%20height%3D%2224%22%20viewBox%3D%220%200%2024%2024%22%20fill%3D%22none%22%20stroke%3D%22%236b7280%22%20stroke-width%3D%222%22%20stroke-linecap%3D%22round%22%20stroke-linejoin%3D%22round%22%3E%3Cpath%20d%3D%22m6%209%206%206%206-6%22%2F%3E%3C%2Fsvg%3E\")] bg-[position:right_8px_center] bg-[size:16px]`;

const emit = defineEmits<{
  'update:modelValue': [value: string | number];
}>();

const selectedValue = computed({
  get: () => props.modelValue ?? '',
  set: (val) => emit('update:modelValue', val),
});
</script>

<template>
  <select
    :id="id"
    v-model="selectedValue"
    :disabled="disabled"
    :class="
      cn(
        'flex h-10 w-full rounded-lg border border-border bg-white px-3 py-2 text-sm text-foreground shadow-xs transition-colors',
        'focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20',
        'disabled:cursor-not-allowed disabled:opacity-50 disabled:bg-gray-50',
        'appearance-none bg-no-repeat',
        selectBgUrl,
        'pr-10',
        error && 'border-danger focus:border-danger focus:ring-danger/20',
        ($attrs.class as string)
      )
    "
  >
    <option v-if="placeholder" value="" disabled>
      {{ placeholder }}
    </option>
    <option
      v-for="option in options"
      :key="option.value"
      :value="option.value"
      :disabled="option.disabled"
    >
      {{ option.label }}
    </option>
  </select>
</template>
