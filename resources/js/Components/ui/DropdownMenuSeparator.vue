<script setup lang="ts">
import { cn } from '@/lib/utils';
import { DropdownMenuSeparator } from 'reka-ui';

defineProps<{}>();
</script>

<template>
  <DropdownMenuSeparator
    :class="cn('-mx-1 my-1 h-px bg-border', $attrs.class as string)"
  />
</template>
