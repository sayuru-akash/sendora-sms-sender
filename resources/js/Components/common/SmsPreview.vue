<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{
  message: string;
  senderId?: string;
}>();

const formattedTime = computed(() => {
  return new Date().toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
});
</script>

<template>
  <div class="mx-auto w-[280px]">
    <!-- Phone frame -->
    <div class="rounded-[32px] border-[3px] border-gray-800 bg-gray-900 p-2 shadow-xl">
      <div class="rounded-[24px] bg-white overflow-hidden">
        <!-- Status bar -->
        <div class="flex items-center justify-between px-5 py-2 bg-gray-50">
          <span class="text-[10px] font-medium text-gray-600">9:41</span>
          <div class="flex gap-1">
            <div class="h-2 w-4 rounded-sm bg-gray-400" />
            <div class="h-2 w-4 rounded-sm bg-gray-400" />
            <div class="h-2 w-4 rounded-sm bg-gray-400" />
          </div>
        </div>

        <!-- SMS Header -->
        <div class="px-4 py-3 border-b border-gray-100">
          <p class="text-xs font-semibold text-gray-800">{{ senderId ?? 'Sendora' }}</p>
        </div>

        <!-- Message bubble -->
        <div class="px-4 py-4 min-h-[120px]">
          <div class="flex justify-end">
            <div class="max-w-[85%]">
              <div class="bg-primary text-white rounded-2xl rounded-br-md px-3.5 py-2.5">
                <p class="text-[13px] leading-relaxed whitespace-pre-wrap break-words">
                  {{ message || 'Type your message to see a preview...' }}
                </p>
              </div>
              <p class="text-[10px] text-gray-400 text-right mt-1">{{ formattedTime }}</p>
            </div>
          </div>
        </div>

        <!-- Input bar -->
        <div class="px-3 py-2 border-t border-gray-100">
          <div class="flex items-center gap-2">
            <div class="flex-1 rounded-full bg-gray-100 px-3 py-1.5">
              <p class="text-[11px] text-gray-400">iMessage</p>
            </div>
          </div>
        </div>

        <!-- Home indicator -->
        <div class="flex justify-center pb-2">
          <div class="w-24 h-1 rounded-full bg-gray-300" />
        </div>
      </div>
    </div>
  </div>
</template>
