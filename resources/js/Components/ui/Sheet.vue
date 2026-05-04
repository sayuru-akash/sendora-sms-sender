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

const props = withDefaults(
  defineProps<{
    open?: boolean;
    side?: 'left' | 'right' | 'top' | 'bottom';
    title?: string;
    description?: string;
    contentClass?: string;
  }>(),
  {
    side: 'right',
  }
);

const emit = defineEmits<{
  'update:open': [value: boolean];
}>();

const isOpen = computed({
  get: () => props.open ?? false,
  set: (val) => emit('update:open', val),
});

const sideClasses: Record<string, string> = {
  left: 'inset-y-0 left-0 h-full w-3/4 border-r sm:max-w-sm data-[state=closed]:slide-out-to-left data-[state=open]:slide-in-from-left',
  right: 'inset-y-0 right-0 h-full w-3/4 border-l sm:max-w-sm data-[state=closed]:slide-out-to-right data-[state=open]:slide-in-from-right',
  top: 'inset-x-0 top-0 border-b data-[state=closed]:slide-out-to-top data-[state=open]:slide-in-from-top',
  bottom: 'inset-x-0 bottom-0 border-t data-[state=closed]:slide-out-to-bottom data-[state=open]:slide-in-from-bottom',
};
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
            'fixed z-50 bg-white shadow-lg transition ease-in-out',
            'data-[state=closed]:duration-300 data-[state=open]:duration-500',
            'data-[state=open]:animate-in data-[state=closed]:animate-out',
            sideClasses[side],
            contentClass
          )
        "
      >
        <div class="flex flex-col h-full">
          <div class="flex items-center justify-between border-b border-border px-6 py-4">
            <div>
              <DialogTitle v-if="title" class="text-lg font-semibold text-foreground">
                {{ title }}
              </DialogTitle>
              <DialogDescription v-if="description" class="mt-1 text-sm text-muted">
                {{ description }}
              </DialogDescription>
            </div>
            <DialogClose
              class="rounded-md p-1.5 text-muted-foreground opacity-70 transition-opacity hover:opacity-100 hover:bg-gray-100 focus:outline-none"
            >
              <X class="h-4 w-4" />
              <span class="sr-only">Close</span>
            </DialogClose>
          </div>

          <div class="flex-1 overflow-y-auto p-6">
            <slot />
          </div>

          <div v-if="$slots.footer" class="border-t border-border px-6 py-4">
            <slot name="footer" />
          </div>
        </div>
      </DialogContent>
    </DialogPortal>
  </DialogRoot>
</template>
