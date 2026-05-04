<script setup lang="ts">
import { computed } from 'vue';
import { calculateSmsSegments } from '@/types/template';
import { cn } from '@/lib/utils';

const props = defineProps<{
  text: string;
  maxLength?: number;
}>();

const charCount = computed(() => props.text.length);
const segments = computed(() => calculateSmsSegments(props.text));
const isLong = computed(() => segments.value > 3);
const isOverLimit = computed(() => props.maxLength ? charCount.value > props.maxLength : false);
</script>

<template>
  <div class="flex items-center gap-3 text-xs">
    <span
      :class="cn(
        'text-muted',
        isOverLimit && 'text-danger font-medium'
      )"
    >
      {{ charCount }}{{ maxLength ? ` / ${maxLength}` : '' }} characters
    </span>
    <span class="text-muted-foreground">·</span>
    <span
      :class="cn(
        'text-muted',
        isLong && 'text-warning font-medium',
        segments > 5 && 'text-danger font-medium'
      )"
    >
      {{ segments }} SMS {{ segments === 1 ? 'segment' : 'segments' }}
    </span>
    <span v-if="isLong" class="text-warning">
      (Long message)
    </span>
  </div>
</template>
