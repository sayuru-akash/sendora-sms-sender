<script setup lang="ts">
import Skeleton from '@/Components/ui/Skeleton.vue';
</script>

<template>
  <div class="space-y-4">
    <div class="flex items-center justify-between">
      <Skeleton class="h-8 w-48" />
      <Skeleton class="h-8 w-32" />
    </div>
    <Skeleton class="h-10 w-full" />
    <div class="rounded-xl border border-border bg-white overflow-hidden">
      <div class="p-4 space-y-3">
        <div v-for="i in 5" :key="i" class="flex items-center gap-4">
          <Skeleton class="h-4 w-4" />
          <Skeleton class="h-4 flex-1" />
          <Skeleton class="h-4 w-24" />
          <Skeleton class="h-4 w-20" />
          <Skeleton class="h-4 w-16" />
        </div>
      </div>
    </div>
  </div>
</template>
