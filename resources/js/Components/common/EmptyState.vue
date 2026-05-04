<script setup lang="ts">
import { Inbox } from 'lucide-vue-next';
import type { Component } from 'vue';

defineProps<{
  icon?: Component;
  title?: string;
  description?: string;
}>();
</script>

<template>
  <div class="flex flex-col items-center justify-center py-16 px-4 text-center">
    <div class="flex h-14 w-14 items-center justify-center rounded-full bg-gray-100 mb-4">
      <component :is="icon ?? Inbox" class="h-7 w-7 text-muted-foreground" />
    </div>
    <h3 class="text-lg font-semibold text-foreground">{{ title ?? 'No data found' }}</h3>
    <p class="mt-1 max-w-sm text-sm text-muted">
      {{ description ?? 'There are no items to display at the moment.' }}
    </p>
    <div v-if="$slots.action" class="mt-4">
      <slot name="action" />
    </div>
  </div>
</template>
