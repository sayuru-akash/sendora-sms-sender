<script setup lang="ts">
import { cn } from '@/lib/utils';
import { ref, computed } from 'vue';
import { User } from 'lucide-vue-next';

const props = defineProps<{
  src?: string | null;
  alt?: string;
  fallback?: string;
  size?: 'sm' | 'default' | 'lg' | 'xl';
  class?: string;
}>();

const hasError = ref(false);

const sizeClasses = computed(() => {
  const sizes = {
    sm: 'h-8 w-8 text-xs',
    default: 'h-10 w-10 text-sm',
    lg: 'h-12 w-12 text-base',
    xl: 'h-16 w-16 text-lg',
  };
  return sizes[props.size ?? 'default'];
});

const showImage = computed(() => props.src && !hasError.value);

const fallbackText = computed(() => {
  if (props.fallback) return props.fallback;
  if (props.alt) {
    return props.alt
      .split(' ')
      .map((n) => n[0])
      .join('')
      .toUpperCase()
      .substring(0, 2);
  }
  return '';
});
</script>

<template>
  <div
    :class="
      cn(
        'relative inline-flex items-center justify-center overflow-hidden rounded-full bg-gray-100 shrink-0',
        sizeClasses,
        $attrs.class as string
      )
    "
  >
    <img
      v-if="showImage"
      :src="src!"
      :alt="alt"
      class="h-full w-full object-cover"
      @error="hasError = true"
    />
    <span
      v-else-if="fallbackText"
      class="font-medium text-foreground-muted"
    >
      {{ fallbackText }}
    </span>
    <User v-else class="h-1/2 w-1/2 text-muted-foreground" />
  </div>
</template>
