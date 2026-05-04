<script setup lang="ts">
import Badge from '@/Components/ui/Badge.vue';
import { computed } from 'vue';

const props = defineProps<{
  status: string;
}>();

const variantMap: Record<string, 'default' | 'secondary' | 'success' | 'danger' | 'warning' | 'info' | 'outline'> = {
  // Contact statuses
  active: 'success',
  inactive: 'secondary',
  unsubscribed: 'warning',
  blocked: 'danger',
  invalid: 'danger',
  // Campaign statuses
  draft: 'secondary',
  scheduled: 'info',
  sending: 'warning',
  paused: 'warning',
  completed: 'success',
  failed: 'danger',
  cancelled: 'danger',
  // Import statuses
  uploading: 'info',
  mapping: 'info',
  processing: 'warning',
  // Template statuses
  // General
  pending: 'info',
  sent: 'success',
  delivered: 'success',
  skipped: 'secondary',
};

const variant = computed(() => variantMap[props.status] ?? 'secondary');
const label = computed(() => {
  return props.status.charAt(0).toUpperCase() + props.status.slice(1).replace(/_/g, ' ');
});
</script>

<template>
  <Badge :variant="variant">
    {{ label }}
  </Badge>
</template>
