<script setup lang="ts">
import Card from '@/Components/ui/Card.vue';
import { formatNumber } from '@/lib/utils';
import { cn } from '@/lib/utils';
import type { Component } from 'vue';
import { TrendingUp, TrendingDown } from 'lucide-vue-next';

const props = defineProps<{
  label: string;
  value: number;
  icon: Component;
  iconColor?: string;
  iconBg?: string;
  change?: number;
  changeLabel?: string;
  format?: 'number' | 'currency' | 'percent';
}>();

const displayValue = () => {
  if (props.format === 'currency') {
    return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'LKR', minimumFractionDigits: 0 }).format(props.value);
  }
  if (props.format === 'percent') {
    return `${props.value}%`;
  }
  return formatNumber(props.value);
};
</script>

<template>
  <Card class="p-6 hover:shadow-sm transition-shadow duration-200">
    <div class="flex items-start justify-between">
      <div class="space-y-2">
        <p class="text-sm font-medium text-muted">{{ label }}</p>
        <p class="text-2xl font-bold text-foreground tracking-tight">{{ displayValue() }}</p>
        <div v-if="change !== undefined" class="flex items-center gap-1">
          <component
            :is="change >= 0 ? TrendingUp : TrendingDown"
            :class="cn('h-3.5 w-3.5', change >= 0 ? 'text-success' : 'text-danger')"
          />
          <span
            :class="cn('text-xs font-medium', change >= 0 ? 'text-success' : 'text-danger')"
          >
            {{ change >= 0 ? '+' : '' }}{{ change }}%
          </span>
          <span v-if="changeLabel" class="text-xs text-muted">{{ changeLabel }}</span>
        </div>
      </div>
      <div
        :class="cn('flex h-11 w-11 items-center justify-center rounded-xl shrink-0', iconBg ?? 'bg-primary-light')"
      >
        <component
          :is="icon"
          :class="cn('h-5 w-5', iconColor ?? 'text-primary')"
        />
      </div>
    </div>
  </Card>
</template>
