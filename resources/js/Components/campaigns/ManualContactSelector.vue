<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import Input from '@/Components/ui/Input.vue';
import Button from '@/Components/ui/Button.vue';
import { cn } from '@/lib/utils';
import { Search, X } from 'lucide-vue-next';

interface ContactOption {
  id: number;
  name: string;
  email: string | null;
  phone: string;
  status: string;
}

const props = defineProps<{
  modelValue: number[];
  error?: string;
}>();

const emit = defineEmits<{
  'update:modelValue': [value: number[]];
}>();

const search = ref('');
const isLoading = ref(false);
const loadError = ref('');
const contacts = ref<ContactOption[]>([]);
const selectedContacts = ref<ContactOption[]>([]);
let searchTimer: ReturnType<typeof setTimeout> | null = null;
let fetchVersion = 0;

const selectedIds = computed(() => new Set(props.modelValue));
const availableContacts = computed(() => contacts.value.filter((contact) => !selectedIds.value.has(contact.id)));
const selectedCount = computed(() => props.modelValue.length);

watch(search, () => {
  if (searchTimer) {
    clearTimeout(searchTimer);
  }

  searchTimer = setTimeout(fetchContacts, 250);
});

watch(
  () => props.modelValue,
  () => {
    selectedContacts.value = selectedContacts.value.filter((contact) => selectedIds.value.has(contact.id));
    fetchContacts();
  },
);

onMounted(fetchContacts);

onBeforeUnmount(() => {
  if (searchTimer) {
    clearTimeout(searchTimer);
  }
});

async function fetchContacts() {
  const version = ++fetchVersion;
  isLoading.value = true;
  loadError.value = '';

  try {
    const response = await window.axios.get(route('campaigns.audience.contacts'), {
      params: {
        search: search.value || undefined,
        ids: props.modelValue,
      },
    });

    if (version !== fetchVersion) {
      return;
    }

    contacts.value = response.data.contacts;
    const byId = new Map([...selectedContacts.value, ...contacts.value].map((contact) => [contact.id, contact]));
    selectedContacts.value = props.modelValue
      .map((id) => byId.get(id))
      .filter((contact): contact is ContactOption => Boolean(contact));
  } catch {
    if (version === fetchVersion) {
      loadError.value = 'Could not load contacts. Try again.';
    }
  } finally {
    if (version === fetchVersion) {
      isLoading.value = false;
    }
  }
}

function selectContact(contact: ContactOption) {
  if (selectedIds.value.has(contact.id)) {
    return;
  }

  selectedContacts.value = [...selectedContacts.value, contact];
  emit('update:modelValue', [...props.modelValue, contact.id]);
}

function removeContact(contact: ContactOption) {
  selectedContacts.value = selectedContacts.value.filter((selected) => selected.id !== contact.id);
  emit('update:modelValue', props.modelValue.filter((id) => id !== contact.id));
}

function clearSelection() {
  selectedContacts.value = [];
  emit('update:modelValue', []);
}
</script>

<template>
  <div
    :class="
      cn(
        'rounded-lg border bg-white p-3',
        error ? 'border-danger' : 'border-border'
      )
    "
  >
    <div class="relative">
      <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
      <Input
        v-model="search"
        class="pl-9"
        placeholder="Search name, phone, or email"
        aria-label="Search contacts"
      />
    </div>

    <div class="mt-2 flex items-center justify-between gap-3 text-xs text-muted">
      <span>{{ selectedCount }} selected</span>
      <Button v-if="selectedContacts.length" size="sm" variant="ghost" @click="clearSelection">Clear</Button>
    </div>

    <div v-if="selectedContacts.length" class="mt-2 flex max-h-28 flex-wrap gap-1.5 overflow-y-auto pr-1">
      <button
        v-for="contact in selectedContacts"
        :key="contact.id"
        type="button"
        class="inline-flex max-w-full items-center gap-1 rounded-full border border-primary-200 bg-primary-50 px-2.5 py-1 text-xs font-medium text-primary-700"
        @click="removeContact(contact)"
      >
        <span class="truncate">{{ contact.name }}</span>
        <X class="h-3 w-3 shrink-0" />
      </button>
    </div>

    <div class="mt-3 max-h-64 overflow-y-auto rounded-md border border-border">
      <button
        v-for="contact in availableContacts"
        :key="contact.id"
        type="button"
        class="flex w-full items-center justify-between gap-3 border-b border-border px-3 py-2 text-left last:border-0 hover:bg-gray-50"
        @click="selectContact(contact)"
      >
        <span class="min-w-0">
          <span class="block truncate text-sm font-medium text-foreground">{{ contact.name }}</span>
          <span class="block truncate text-xs text-muted">{{ contact.phone }}{{ contact.email ? ` · ${contact.email}` : '' }}</span>
        </span>
        <span class="shrink-0 rounded-full bg-primary-light px-2 py-0.5 text-xs font-medium text-primary">
          Add
        </span>
      </button>

      <div v-if="!availableContacts.length" class="px-3 py-6 text-center text-sm text-muted">
        {{ isLoading ? 'Searching...' : 'No matching receivable contacts' }}
      </div>
    </div>

    <p v-if="loadError" class="mt-2 text-xs text-danger">{{ loadError }}</p>
    <p v-if="error" class="mt-2 text-xs text-danger">{{ error }}</p>
  </div>
</template>
