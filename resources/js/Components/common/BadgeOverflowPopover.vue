<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { cn } from '@/lib/utils';

const props = withDefaults(defineProps<{
  items: Array<{ name: string }>;
  label?: string;
  title?: string;
  tone?: 'list' | 'tag';
}>(), {
  label: undefined,
  title: 'More',
  tone: 'tag',
});

const root = ref<HTMLElement | null>(null);
const isHovered = ref(false);
const isPinned = ref(false);

const isOpen = computed(() => isHovered.value || isPinned.value);
const triggerLabel = computed(() => props.label ?? `+${props.items.length}`);
const toneClasses = computed(() => {
  if (props.tone === 'list') {
    return {
      trigger: 'border-blue-200 bg-blue-50 text-blue-700 shadow-blue-100/70 hover:border-blue-300 hover:bg-blue-100 hover:text-blue-800',
      open: 'border-blue-300 bg-blue-100 text-blue-800 ring-blue-100',
      dot: 'bg-blue-500',
    };
  }

  return {
    trigger: 'border-primary-200 bg-primary-50 text-primary-700 shadow-primary-100/70 hover:border-primary-300 hover:bg-primary-100 hover:text-primary-800',
    open: 'border-primary-300 bg-primary-100 text-primary-800 ring-primary-100',
    dot: 'bg-primary-500',
  };
});

function togglePinned() {
  isPinned.value = !isPinned.value;
}

function closePinned(event: MouseEvent) {
  if (root.value?.contains(event.target as Node)) {
    return;
  }

  isPinned.value = false;
}

function closeOnEscape(event: KeyboardEvent) {
  if (event.key === 'Escape') {
    isPinned.value = false;
    isHovered.value = false;
  }
}

onMounted(() => {
  document.addEventListener('click', closePinned);
  document.addEventListener('keydown', closeOnEscape);
});

onBeforeUnmount(() => {
  document.removeEventListener('click', closePinned);
  document.removeEventListener('keydown', closeOnEscape);
});
</script>

<template>
  <span
    ref="root"
    class="relative inline-flex shrink-0"
    @mouseenter="isHovered = true"
    @mouseleave="isHovered = false"
  >
    <button
      type="button"
      :aria-expanded="isOpen"
      :class="
        cn(
          'inline-flex items-center rounded-full border px-2 py-0.5 text-xs font-semibold tabular-nums shadow-sm ring-1 ring-transparent',
          'transition-all duration-150 hover:-translate-y-px focus-visible:outline-none focus-visible:ring-2',
          toneClasses.trigger,
          isOpen && toneClasses.open
        )
      "
      @click.stop="togglePinned"
    >
      {{ triggerLabel }}
    </button>

    <Transition
      enter-active-class="transition duration-150 ease-out"
      enter-from-class="translate-y-1 scale-95 opacity-0"
      enter-to-class="translate-y-0 scale-100 opacity-100"
      leave-active-class="transition duration-100 ease-in"
      leave-from-class="translate-y-0 scale-100 opacity-100"
      leave-to-class="translate-y-1 scale-95 opacity-0"
    >
      <div
        v-if="isOpen"
        class="absolute right-0 top-full z-50 mt-2 w-max min-w-40 max-w-72 origin-top-right rounded-lg border border-border bg-white p-2 shadow-lg shadow-black/10"
      >
        <p class="mb-1 px-1 text-[11px] font-medium uppercase tracking-wide text-muted">
          {{ title }}
        </p>
        <div class="flex max-h-48 flex-col gap-1 overflow-y-auto">
          <span
            v-for="item in items"
            :key="item.name"
            class="flex items-center gap-2 rounded-md px-2 py-1 text-xs font-medium text-foreground transition-colors hover:bg-gray-50"
          >
            <span :class="cn('h-1.5 w-1.5 shrink-0 rounded-full', toneClasses.dot)" />
            {{ item.name }}
          </span>
        </div>
      </div>
    </Transition>
  </span>
</template>
