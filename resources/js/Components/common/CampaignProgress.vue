<script setup lang="ts">
import { computed } from 'vue';
import Progress from '@/Components/ui/Progress.vue';
import { cn } from '@/lib/utils';

const props = defineProps<{
  sent: number;
  failed: number;
  pending: number;
  total: number;
}>();

const sentPercent = computed(() => props.total > 0 ? (props.sent / props.total) * 100 : 0);
const failedPercent = computed(() => props.total > 0 ? (props.failed / props.total) * 100 : 0);
</script>

<template>
  <div :class="cn('space-y-2', $attrs.class as string)">
    <div class="flex items-center justify-between text-sm">
      <span class="text-muted">Progress</span>
      <span class="font-medium text-foreground">
        {{ sent + failed }} / {{ total }}
      </span>
    </div>
    <div class="relative h-3 w-full overflow-hidden rounded-full bg-gray-100">
      <div
        class="absolute inset-y-0 left-0 bg-success transition-all duration-700 ease-out rounded-l-full"
        :style="{ width: `${sentPercent}%` }"
      />
      <div
        class="absolute inset-y-0 bg-danger transition-all duration-700 ease-out"
        :style="{ left: `${sentPercent}%`, width: `${failedPercent}%` }"
        :class="sentPercent === 0 ? 'rounded-l-full' : ''"
      />
    </div>
    <div class="flex items-center gap-4 text-xs">
      <span class="flex items-center gap-1.5">
        <span class="h-2 w-2 rounded-full bg-success" />
        <span class="text-muted">Sent: {{ sent }}</span>
      </span>
      <span class="flex items-center gap-1.5">
        <span class="h-2 w-2 rounded-full bg-danger" />
        <span class="text-muted">Failed: {{ failed }}</span>
      </span>
      <span class="flex items-center gap-1.5">
        <span class="h-2 w-2 rounded-full bg-gray-300" />
        <span class="text-muted">Pending: {{ pending }}</span>
      </span>
    </div>
  </div>
</template>
