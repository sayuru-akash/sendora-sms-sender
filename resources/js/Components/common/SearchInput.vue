<script setup lang="ts">
import { ref, watch } from 'vue';
import { Search, X } from 'lucide-vue-next';
import { cn } from '@/lib/utils';

const props = withDefaults(
  defineProps<{
    modelValue?: string;
    placeholder?: string;
    debounce?: number;
  }>(),
  {
    placeholder: 'Search...',
    debounce: 300,
  }
);

const emit = defineEmits<{
  'update:modelValue': [value: string];
}>();

const localValue = ref(props.modelValue ?? '');
let timer: ReturnType<typeof setTimeout>;

watch(localValue, (val) => {
  clearTimeout(timer);
  timer = setTimeout(() => {
    emit('update:modelValue', val);
  }, props.debounce);
});

watch(
  () => props.modelValue,
  (val) => {
    if (val !== localValue.value) {
      localValue.value = val ?? '';
    }
  }
);

function clear() {
  localValue.value = '';
  emit('update:modelValue', '');
}
</script>

<template>
  <div :class="cn('relative', $attrs.class as string)">
    <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
    <input
      v-model="localValue"
      type="text"
      :aria-label="placeholder"
      :placeholder="placeholder"
      class="h-10 w-full rounded-lg border border-border bg-white pl-10 pr-8 text-sm text-foreground placeholder:text-muted-foreground focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20 transition-colors"
    />
    <button
      v-if="localValue"
      type="button"
      aria-label="Clear search"
      @click="clear"
      class="absolute right-2.5 top-1/2 -translate-y-1/2 rounded-md p-0.5 text-muted-foreground hover:text-foreground hover:bg-gray-100 transition-colors"
    >
      <X class="h-3.5 w-3.5" />
    </button>
  </div>
</template>
