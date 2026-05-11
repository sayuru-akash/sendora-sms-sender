<script setup lang="ts">
import { computed } from 'vue';
import { calculateSmsMetrics } from '@/types/template';
import { cn } from '@/lib/utils';

const props = defineProps<{
  text: string;
}>();

const metrics = computed(() => calculateSmsMetrics(props.text));
const charCount = computed(() => metrics.value.characterCount);
const segments = computed(() => metrics.value.smsSegments);
const isCostly = computed(() => segments.value > 5);
</script>

<template>
  <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs">
    <span class="text-muted">
      {{ charCount }} chars
    </span>
    <span class="text-muted-foreground">·</span>
    <span
      :class="cn(
        'text-muted',
        isCostly && 'text-warning font-medium'
      )"
    >
      {{ segments }} billable segment{{ segments === 1 ? '' : 's' }}
    </span>
    <span class="text-muted-foreground">·</span>
    <span
      :class="cn(
        'rounded-full px-2 py-0.5 font-medium',
        metrics.encoding === 'Unicode' ? 'bg-warning-light text-warning' : 'bg-primary-light text-primary'
      )"
    >
      {{ metrics.encoding }}
    </span>
    <span class="text-muted-foreground">·</span>
    <span class="text-muted">
      {{ metrics.remainingInSegment }} left in current segment
    </span>
    <span v-if="isCostly" class="text-warning">
      Higher send cost
    </span>
  </div>
</template>
