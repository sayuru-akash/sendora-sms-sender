<script setup lang="ts">
import { cn } from '@/lib/utils';
import { ChevronDown } from 'lucide-vue-next';
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

const emit = defineEmits<{
  'update:modelValue': [value: string | number];
}>();

const selectedValue = computed({
  get: () => props.modelValue ?? '',
  set: (val) => emit('update:modelValue', val),
});
</script>

<template>
  <div class="relative">
    <select
      :id="id"
      v-model="selectedValue"
      :disabled="disabled"
      :class="
        cn(
          'flex h-10 w-full rounded-lg border border-border bg-white px-3 py-2 pr-10 text-sm text-foreground shadow-xs transition-colors',
          'focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20',
          'disabled:cursor-not-allowed disabled:opacity-50 disabled:bg-gray-50',
          'appearance-none',
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
    <ChevronDown class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted" />
  </div>
</template>
