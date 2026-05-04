<script setup lang="ts">
import Sheet from '@/Components/ui/Sheet.vue';
import Button from '@/Components/ui/Button.vue';
import { computed } from 'vue';

const props = defineProps<{
  open?: boolean;
  title?: string;
}>();

const emit = defineEmits<{
  'update:open': [value: boolean];
  apply: [];
  reset: [];
}>();

const isOpen = computed({
  get: () => props.open ?? false,
  set: (val) => emit('update:open', val),
});
</script>

<template>
  <Sheet
    v-model:open="isOpen"
    side="right"
    :title="title ?? 'Filters'"
    description="Refine your results with filters"
    content-class="sm:max-w-md"
  >
    <div class="space-y-4">
      <slot />
    </div>

    <template #footer>
      <div class="flex items-center justify-between w-full">
        <Button variant="ghost" @click="emit('reset')">
          Reset Filters
        </Button>
        <Button @click="emit('apply')">
          Apply Filters
        </Button>
      </div>
    </template>
  </Sheet>
</template>
