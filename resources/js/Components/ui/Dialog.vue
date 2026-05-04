<script setup lang="ts">
import { cn } from '@/lib/utils';
import {
  DialogRoot,
  DialogTrigger,
  DialogPortal,
  DialogOverlay,
  DialogContent,
  DialogClose,
  DialogTitle,
  DialogDescription,
} from 'reka-ui';
import { X } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{
  open?: boolean;
  class?: string;
  contentClass?: string;
  title?: string;
  description?: string;
}>();

const emit = defineEmits<{
  'update:open': [value: boolean];
}>();

const isOpen = computed({
  get: () => props.open ?? false,
  set: (val) => emit('update:open', val),
});
</script>

<template>
  <DialogRoot v-model:open="isOpen">
    <DialogTrigger as-child>
      <slot name="trigger" />
    </DialogTrigger>

    <DialogPortal>
      <DialogOverlay class="fixed inset-0 z-50 bg-black/40 data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0" />

      <DialogContent
        :class="
          cn(
            'fixed left-1/2 top-1/2 z-50 -translate-x-1/2 -translate-y-1/2',
            'w-full max-w-lg rounded-xl border border-border bg-white p-6 shadow-lg',
            'data-[state=open]:animate-in data-[state=closed]:animate-out',
            'data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0',
            'data-[state=closed]:zoom-out-95 data-[state=open]:zoom-in-95',
            'data-[state=closed]:slide-out-to-left-1/2 data-[state=closed]:slide-out-to-top-[48%]',
            'data-[state=open]:slide-in-from-left-1/2 data-[state=open]:slide-in-from-top-[48%]',
            'duration-200',
            contentClass
          )
        "
      >
        <DialogTitle v-if="title" class="text-lg font-semibold text-foreground">
          {{ title }}
        </DialogTitle>
        <DialogDescription v-if="description" class="mt-1 text-sm text-muted">
          {{ description }}
        </DialogDescription>

        <slot />

        <DialogClose
          class="absolute right-4 top-4 rounded-md p-1 text-muted-foreground opacity-70 transition-opacity hover:opacity-100 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-primary/50"
        >
          <X class="h-4 w-4" />
          <span class="sr-only">Close</span>
        </DialogClose>
      </DialogContent>
    </DialogPortal>
  </DialogRoot>
</template>
