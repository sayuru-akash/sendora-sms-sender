<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { Check, ChevronDown, Search, X } from 'lucide-vue-next';
import Button from '@/Components/ui/Button.vue';
import Input from '@/Components/ui/Input.vue';
import { cn } from '@/lib/utils';

type Option = {
  id: number;
  name: string;
  color?: string | null;
  colour?: string | null;
  contacts_count?: number;
};

const props = withDefaults(defineProps<{
  modelValue: number[];
  options: Option[];
  placeholder?: string;
  searchPlaceholder?: string;
  emptyText?: string;
  disabled?: boolean;
  maxVisible?: number;
}>(), {
  placeholder: 'Select options',
  searchPlaceholder: 'Search...',
  emptyText: 'No options found',
  maxVisible: 3,
});

const emit = defineEmits<{
  'update:modelValue': [value: number[]];
}>();

const isOpen = ref(false);
const query = ref('');
const root = ref<HTMLElement | null>(null);
const highlightedIndex = ref(-1);
const comboboxId = `multi-select-${Math.random().toString(36).slice(2, 10)}`;
const searchId = `${comboboxId}-search`;
const listboxId = `${comboboxId}-listbox`;

const selectedOptions = computed(() => props.options.filter((option) => props.modelValue.includes(option.id)));
const visibleSelected = computed(() => selectedOptions.value.slice(0, props.maxVisible));
const overflowCount = computed(() => Math.max(0, selectedOptions.value.length - props.maxVisible));
const filteredOptions = computed(() => {
  const term = query.value.trim().toLowerCase();

  if (!term) {
    return props.options;
  }

  return props.options.filter((option) => option.name.toLowerCase().includes(term));
});
const activeDescendantId = computed(() => {
  const option = filteredOptions.value[highlightedIndex.value];

  return option ? optionId(option.id) : undefined;
});

function optionColor(option: Option): string | null {
  return option.color ?? option.colour ?? null;
}

function toggleOpen(): void {
  if (props.disabled) {
    return;
  }

  if (isOpen.value) {
    close();

    return;
  }

  open();
}

function open(): void {
  if (props.disabled) {
    return;
  }

  isOpen.value = true;
  highlightedIndex.value = filteredOptions.value.length > 0 ? 0 : -1;

  nextTick(() => {
    root.value?.querySelector<HTMLInputElement>(`#${searchId}`)?.focus();
  });
}

function close(): void {
  isOpen.value = false;
  query.value = '';
  highlightedIndex.value = -1;
}

function isSelected(id: number): boolean {
  return props.modelValue.includes(id);
}

function toggleOption(id: number): void {
  const selected = new Set(props.modelValue);

  if (selected.has(id)) {
    selected.delete(id);
  } else {
    selected.add(id);
  }

  emit('update:modelValue', Array.from(selected));
}

function optionId(id: number): string {
  return `${comboboxId}-option-${id}`;
}

function removeOption(id: number): void {
  emit('update:modelValue', props.modelValue.filter((selectedId) => selectedId !== id));
}

function clear(): void {
  emit('update:modelValue', []);
}

function moveHighlight(direction: 1 | -1): void {
  if (!isOpen.value) {
    open();

    return;
  }

  const total = filteredOptions.value.length;

  if (total === 0) {
    highlightedIndex.value = -1;

    return;
  }

  highlightedIndex.value = (highlightedIndex.value + direction + total) % total;
}

function selectHighlighted(): void {
  const option = filteredOptions.value[highlightedIndex.value];

  if (!option) {
    return;
  }

  toggleOption(option.id);
}

function onDocumentPointerDown(event: PointerEvent): void {
  if (!root.value?.contains(event.target as Node)) {
    close();
  }
}

function onKeydown(event: KeyboardEvent): void {
  if (event.key === 'Escape' && isOpen.value) {
    close();
  }
}

function onTriggerKeydown(event: KeyboardEvent): void {
  if (event.key === 'ArrowDown') {
    event.preventDefault();
    open();
    highlightedIndex.value = 0;
  }

  if (event.key === 'ArrowUp') {
    event.preventDefault();
    open();
    highlightedIndex.value = filteredOptions.value.length - 1;
  }
}

function onSearchKeydown(event: KeyboardEvent): void {
  if (event.key === 'ArrowDown') {
    event.preventDefault();
    moveHighlight(1);
  }

  if (event.key === 'ArrowUp') {
    event.preventDefault();
    moveHighlight(-1);
  }

  if (event.key === 'Enter') {
    event.preventDefault();
    selectHighlighted();
  }

  if (event.key === 'Escape') {
    event.preventDefault();
    close();
  }
}

watch(filteredOptions, (options) => {
  if (!isOpen.value) {
    return;
  }

  if (options.length === 0) {
    highlightedIndex.value = -1;

    return;
  }

  highlightedIndex.value = Math.min(Math.max(highlightedIndex.value, 0), options.length - 1);
});

onMounted(() => {
  document.addEventListener('pointerdown', onDocumentPointerDown);
  document.addEventListener('keydown', onKeydown);
});

onBeforeUnmount(() => {
  document.removeEventListener('pointerdown', onDocumentPointerDown);
  document.removeEventListener('keydown', onKeydown);
});
</script>

<template>
  <div ref="root" class="relative">
    <button
      type="button"
      :disabled="disabled"
      role="combobox"
      :aria-label="placeholder"
      :aria-expanded="isOpen"
      :aria-controls="listboxId"
      :aria-haspopup="'listbox'"
      :aria-activedescendant="activeDescendantId"
      :class="
        cn(
          'flex min-h-11 w-full items-center justify-between gap-3 rounded-lg border border-border bg-white px-3 py-2 text-left shadow-xs transition-all duration-150',
          'hover:border-muted-foreground/60 focus:outline-none focus:ring-2 focus:ring-primary/20',
          isOpen && 'border-primary ring-2 ring-primary/20',
          disabled && 'cursor-not-allowed opacity-60'
        )
      "
      @click="toggleOpen"
      @keydown="onTriggerKeydown"
    >
      <div class="flex min-w-0 flex-1 flex-wrap items-center gap-1.5">
        <span v-if="selectedOptions.length === 0" class="text-sm text-muted">
          {{ placeholder }}
        </span>

        <span
          v-for="option in visibleSelected"
          :key="option.id"
          class="inline-flex max-w-full items-center gap-1.5 rounded-md border border-border bg-gray-50 px-2 py-1 text-xs font-medium text-foreground"
        >
          <span
            v-if="optionColor(option)"
            class="h-2 w-2 shrink-0 rounded-full"
            :style="{ backgroundColor: optionColor(option) ?? undefined }"
          />
          <span class="truncate">{{ option.name }}</span>
          <span
            class="rounded p-0.5 text-muted-foreground hover:bg-gray-200 hover:text-foreground"
            role="button"
            tabindex="0"
            :aria-label="`Remove ${option.name}`"
            @click.stop="removeOption(option.id)"
            @keydown.enter.stop.prevent="removeOption(option.id)"
            @keydown.space.stop.prevent="removeOption(option.id)"
          >
            <X class="h-3 w-3" />
          </span>
        </span>

        <span
          v-if="overflowCount"
          class="inline-flex items-center rounded-md border border-primary/20 bg-primary/10 px-2 py-1 text-xs font-semibold text-primary"
        >
          +{{ overflowCount }}
        </span>
      </div>

      <ChevronDown :class="cn('h-4 w-4 shrink-0 text-muted-foreground transition-transform', isOpen && 'rotate-180')" />
    </button>

    <Transition
      enter-active-class="transition ease-out duration-150"
      enter-from-class="opacity-0 translate-y-1 scale-[0.98]"
      enter-to-class="opacity-100 translate-y-0 scale-100"
      leave-active-class="transition ease-in duration-100"
      leave-from-class="opacity-100 translate-y-0 scale-100"
      leave-to-class="opacity-0 translate-y-1 scale-[0.98]"
    >
      <div
        v-if="isOpen"
        :id="listboxId"
        role="listbox"
        aria-multiselectable="true"
        class="absolute z-40 mt-2 w-full overflow-hidden rounded-xl border border-border bg-white shadow-lg"
      >
        <div class="border-b border-border p-2">
          <div class="relative">
            <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
            <Input
              :id="searchId"
              v-model="query"
              role="searchbox"
              :aria-controls="listboxId"
              :aria-activedescendant="activeDescendantId"
              :placeholder="searchPlaceholder"
              class="pl-9"
              autofocus
              @keydown="onSearchKeydown"
            />
          </div>
        </div>

        <div class="max-h-64 overflow-y-auto p-1">
          <button
            v-for="(option, index) in filteredOptions"
            :key="option.id"
            :id="optionId(option.id)"
            type="button"
            role="option"
            :aria-selected="isSelected(option.id)"
            :class="
              cn(
                'flex w-full items-center gap-3 rounded-lg px-3 py-2 text-left text-sm transition-colors hover:bg-gray-50',
                highlightedIndex === index && 'bg-gray-50'
              )
            "
            @click="toggleOption(option.id)"
            @mouseenter="highlightedIndex = index"
          >
            <span
              :class="
                cn(
                  'flex h-4 w-4 items-center justify-center rounded border',
                  isSelected(option.id) ? 'border-primary bg-primary text-white' : 'border-border bg-white'
                )
              "
            >
              <Check v-if="isSelected(option.id)" class="h-3 w-3" />
            </span>
            <span
              v-if="optionColor(option)"
              class="h-2.5 w-2.5 shrink-0 rounded-full"
              :style="{ backgroundColor: optionColor(option) ?? undefined }"
            />
            <span class="min-w-0 flex-1 truncate text-foreground">{{ option.name }}</span>
            <span v-if="typeof option.contacts_count === 'number'" class="text-xs text-muted">
              {{ option.contacts_count }}
            </span>
          </button>

          <div v-if="filteredOptions.length === 0" role="status" class="px-3 py-8 text-center text-sm text-muted">
            {{ emptyText }}
          </div>
        </div>

        <div v-if="selectedOptions.length" class="flex items-center justify-between border-t border-border px-3 py-2">
          <span class="text-xs text-muted">{{ selectedOptions.length }} selected</span>
          <Button variant="ghost" size="sm" aria-label="Clear selected options" @click="clear">Clear</Button>
        </div>
      </div>
    </Transition>
  </div>
</template>
