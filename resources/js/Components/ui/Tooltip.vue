<script setup lang="ts">
import { cn } from '@/lib/utils';
import { TooltipRoot, TooltipTrigger, TooltipPortal, TooltipContent, TooltipArrow } from 'reka-ui';

defineProps<{
  text: string;
  side?: 'top' | 'right' | 'bottom' | 'left';
  class?: string;
}>();
</script>

<template>
  <TooltipRoot>
    <TooltipTrigger as-child>
      <slot />
    </TooltipTrigger>

    <TooltipPortal>
      <TooltipContent
        :side="side ?? 'top'"
        :side-offset="4"
        :class="
          cn(
            'z-50 overflow-hidden rounded-md bg-sidebar px-3 py-1.5 text-xs text-white shadow-md',
            'animate-in fade-in-0 zoom-in-95',
            'data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=closed]:zoom-out-95',
            $attrs.class as string
          )
        "
      >
        {{ text }}
        <TooltipArrow class="fill-sidebar" :width="10" :height="5" />
      </TooltipContent>
    </TooltipPortal>
  </TooltipRoot>
</template>
