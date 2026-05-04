<script setup lang="ts">
import { cn } from '@/lib/utils';
import { X } from 'lucide-vue-next';

defineProps<{
  name: string;
  color?: string;
  removable?: boolean;
  class?: string;
}>();

defineEmits<{
  remove: [];
}>();
</script>

<template>
  <span
    :class="
      cn(
        'inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium border',
        color ?? 'bg-gray-100 text-gray-700 border-gray-200',
        $attrs.class as string
      )
    "
  >
    {{ name }}
    <button
      v-if="removable"
      @click.stop="$emit('remove')"
      class="ml-0.5 rounded-full p-0.5 hover:bg-black/10 transition-colors"
    >
      <X class="h-3 w-3" />
    </button>
  </span>
</template>
