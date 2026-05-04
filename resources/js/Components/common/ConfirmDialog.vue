<script setup lang="ts">
import { cn } from '@/lib/utils';
import Button from '@/Components/ui/Button.vue';
import {
  DialogRoot,
  DialogPortal,
  DialogOverlay,
  DialogContent,
  DialogTitle,
  DialogDescription,
} from 'reka-ui';
import { AlertTriangle } from 'lucide-vue-next';

const props = withDefaults(
  defineProps<{
    open?: boolean;
    title?: string;
    message?: string;
    confirmLabel?: string;
    cancelLabel?: string;
    variant?: 'default' | 'destructive';
    loading?: boolean;
  }>(),
  {
    title: 'Confirm Action',
    message: 'Are you sure you want to proceed?',
    confirmLabel: 'Confirm',
    cancelLabel: 'Cancel',
    variant: 'default',
  }
);

const emit = defineEmits<{
  confirm: [];
  cancel: [];
  'update:open': [value: boolean];
}>();
</script>

<template>
  <DialogRoot :open="open" @update:open="emit('update:open', $event as boolean)">
    <DialogPortal>
      <DialogOverlay class="fixed inset-0 z-[60] bg-black/40 data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0" />
      <DialogContent
        class="fixed left-1/2 top-1/2 z-[60] -translate-x-1/2 -translate-y-1/2 w-full max-w-md rounded-xl border border-border bg-white p-6 shadow-lg data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0 data-[state=closed]:zoom-out-95 data-[state=open]:zoom-in-95 duration-200"
      >
        <div class="flex items-start gap-4">
          <div
            v-if="variant === 'destructive'"
            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-danger-light"
          >
            <AlertTriangle class="h-5 w-5 text-danger" />
          </div>
          <div class="flex-1">
            <DialogTitle class="text-lg font-semibold text-foreground">
              {{ title }}
            </DialogTitle>
            <DialogDescription class="mt-2 text-sm text-muted">
              {{ message }}
            </DialogDescription>
          </div>
        </div>

        <div class="mt-6 flex justify-end gap-3">
          <Button variant="outline" @click="emit('cancel')" :disabled="loading">
            {{ cancelLabel }}
          </Button>
          <Button
            :variant="variant === 'destructive' ? 'destructive' : 'default'"
            :loading="loading"
            @click="emit('confirm')"
          >
            {{ confirmLabel }}
          </Button>
        </div>
      </DialogContent>
    </DialogPortal>
  </DialogRoot>
</template>
