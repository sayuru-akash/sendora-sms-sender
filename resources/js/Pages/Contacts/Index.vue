<script setup lang="ts">
import { ref, computed } from 'vue';
import AppLayout from '@/Components/layout/AppLayout.vue';
import PageHeader from '@/Components/common/PageHeader.vue';
import SearchInput from '@/Components/common/SearchInput.vue';
import StatusBadge from '@/Components/common/StatusBadge.vue';
import TagPill from '@/Components/common/TagPill.vue';
import ListBadge from '@/Components/common/ListBadge.vue';
import BadgeOverflowPopover from '@/Components/common/BadgeOverflowPopover.vue';
import FilterDrawer from '@/Components/common/FilterDrawer.vue';
import DataTable from '@/Components/common/DataTable.vue';
import Button from '@/Components/ui/Button.vue';
import Label from '@/Components/ui/Label.vue';
import Select from '@/Components/ui/Select.vue';
import Input from '@/Components/ui/Input.vue';
import Avatar from '@/Components/ui/Avatar.vue';
import DropdownMenu from '@/Components/ui/DropdownMenu.vue';
import DropdownMenuItem from '@/Components/ui/DropdownMenuItem.vue';
import DropdownMenuSeparator from '@/Components/ui/DropdownMenuSeparator.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import {
  UserPlus,
  Upload,
  Download,
  Filter,
  MoreHorizontal,
  Eye,
  Pencil,
  Trash2,
  Tag,
  List,
} from 'lucide-vue-next';
import type { Contact } from '@/types/contact';
import type { Tag as TagType, ListModel, Pagination } from '@/types';
import { createColumnHelper } from '@tanstack/vue-table';
import { h } from 'vue';
import { formatDateTime, formatNumber, formatRelativeTime } from '@/lib/utils';
import { useConfirm } from '@/composables/useConfirm';
import { toast } from 'vue-sonner';

const props = defineProps<{
  contacts: { data: Contact[]; meta: Pagination };
  tags: TagType[];
  lists: ListModel[];
  sourceOptions: string[];
  filters: Record<string, string | undefined>;
}>();

const { confirm } = useConfirm();
const search = ref(props.filters.search ?? '');
const filtersOpen = ref(false);
const bulkProcessing = ref(false);
const bulkTagId = ref('');
const bulkListId = ref('');
const selectedFilters = ref({
  status: props.filters.status ?? '',
  tag_id: props.filters.tag_id ?? '',
  list_id: props.filters.list_id ?? '',
  source: props.filters.source ?? '',
  city: props.filters.city ?? '',
});

const statusOptions = [
  { label: 'All', value: '' },
  { label: 'Active', value: 'active' },
  { label: 'Inactive', value: 'inactive' },
  { label: 'Unsubscribed', value: 'unsubscribed' },
  { label: 'Blocked', value: 'blocked' },
  { label: 'Invalid', value: 'invalid' },
];

const tagOptions = computed(() => [
  { label: 'All', value: '' },
  ...props.tags.map((t) => ({ label: t.name, value: String(t.id) })),
]);

const listOptions = computed(() => [
  { label: 'All', value: '' },
  ...props.lists.map((l) => ({ label: l.name, value: String(l.id) })),
]);

const sourceFilterOptions = computed(() => {
  const liveSources = new Set(props.sourceOptions);

  if (selectedFilters.value.source) {
    liveSources.add(selectedFilters.value.source);
  }

  return [
    { label: 'All', value: '' },
    ...[...liveSources].sort((first, second) => first.localeCompare(second)).map((source) => ({
      label: source,
      value: source,
    })),
  ];
});

const activeSortBy = computed(() => props.filters.sort_by ?? 'created_at');
const activeSortDir = computed<'asc' | 'desc'>(() => props.filters.sort_dir === 'asc' ? 'asc' : 'desc');

function requestParams(overrides: Record<string, string | undefined> = {}) {
  return {
    search: search.value || undefined,
    ...Object.fromEntries(
      Object.entries(selectedFilters.value).filter(([, value]) => value !== '')
    ),
    sort_by: activeSortBy.value,
    sort_dir: activeSortDir.value,
    ...overrides,
  };
}

function applyFilters() {
  router.get(route('contacts.index'), requestParams(), { preserveState: true });
}

function resetFilters() {
  selectedFilters.value = { status: '', tag_id: '', list_id: '', source: '', city: '' };
  applyFilters();
}

function handleSearch(val: string) {
  search.value = val;
  applyFilters();
}

function handleSortChange(sort: { sortBy?: string; sortDir?: 'asc' | 'desc' }) {
  router.get(
    route('contacts.index'),
    requestParams({
      sort_by: sort.sortBy ?? 'created_at',
      sort_dir: sort.sortDir ?? 'desc',
    }),
    {
      preserveScroll: true,
      preserveState: true,
      replace: true,
    }
  );
}

async function deleteContact(contact: Contact) {
  const confirmed = await confirm({
    title: 'Delete Contact',
    message: `Are you sure you want to delete ${contact.full_name}? This action cannot be undone.`,
    confirmLabel: 'Delete',
    variant: 'destructive',
  });
  if (confirmed) {
    router.delete(route('contacts.destroy', contact.id));
  }
}

function exportContacts() {
  window.open(route('contacts.export', { ...selectedFilters.value, search: search.value || undefined }));
}

async function bulkDelete(rows: Contact[], clearSelection: () => void) {
  const confirmed = await confirm({
    title: 'Delete Contacts',
    message: `Delete ${rows.length} selected contact${rows.length === 1 ? '' : 's'}? This action cannot be undone.`,
    confirmLabel: 'Delete',
    variant: 'destructive',
  });

  if (!confirmed) return;

  await runBulkAction({ action: 'delete', contact_ids: rows.map((row) => row.id) }, clearSelection);
}

async function bulkAddTag(rows: Contact[], clearSelection: () => void) {
  if (!bulkTagId.value) {
    toast.error('Select a tag first');
    return;
  }

  await runBulkAction({
    action: 'tag',
    contact_ids: rows.map((row) => row.id),
    tag_ids: [Number(bulkTagId.value)],
  }, clearSelection);
  bulkTagId.value = '';
}

async function bulkAddToList(rows: Contact[], clearSelection: () => void) {
  if (!bulkListId.value) {
    toast.error('Select a list first');
    return;
  }

  await runBulkAction({
    action: 'add_to_list',
    contact_ids: rows.map((row) => row.id),
    list_id: Number(bulkListId.value),
  }, clearSelection);
  bulkListId.value = '';
}

async function runBulkAction(payload: Record<string, unknown>, clearSelection: () => void) {
  bulkProcessing.value = true;

  try {
    const response = await window.axios.post(route('contacts.bulk-action'), payload);
    toast.success(response.data.message ?? 'Contacts updated');
    clearSelection();
    router.reload({ only: ['contacts'] });
  } catch (error) {
    toast.error('Bulk action failed');
  } finally {
    bulkProcessing.value = false;
  }
}

const columnHelper = createColumnHelper<Contact>();

function orderedTags(tags: TagType[]) {
  return [...tags].sort((first, second) => tagPriority(first.name) - tagPriority(second.name));
}

function tagPriority(name: string) {
  if (/^CCB-[A-Z]+26$/.test(name)) {
    return 0;
  }

  if (name === 'CCB - 26.1') {
    return 1;
  }

  if (name === 'CCB') {
    return 2;
  }

  return 3;
}

function renderTimestamp(value?: string | null) {
  if (!value) {
    return h('span', { class: 'text-xs text-muted-foreground' }, '—');
  }

  return h('div', { class: 'min-w-[9.5rem]' }, [
    h('p', { class: 'whitespace-nowrap text-sm font-medium text-foreground' }, formatRelativeTime(value)),
    h('p', { class: 'mt-0.5 whitespace-nowrap text-[11px] text-muted' }, formatDateTime(value)),
  ]);
}

const columns = [
  columnHelper.accessor('full_name', {
    header: 'Name',
    size: 230,
    cell: (info) => {
      const contact = info.row.original;
      return h('div', { class: 'flex items-center gap-3' }, [
        h(Avatar, { alt: contact.full_name, size: 'sm' }),
        h('div', { class: 'min-w-0' }, [
          h('p', { class: 'text-sm font-medium text-foreground truncate' }, contact.full_name),
          contact.email ? h('p', { class: 'text-xs text-muted truncate' }, contact.email) : null,
        ]),
      ]);
    },
    enableSorting: true,
  }),
  columnHelper.accessor('phone', {
    header: 'Phone',
    size: 125,
    cell: (info) => h('span', { class: 'text-sm text-foreground' }, info.getValue()),
  }),
  columnHelper.accessor('company', {
    header: 'Company',
    size: 95,
    cell: (info) => h('span', { class: 'text-sm text-muted' }, info.getValue() ?? '—'),
  }),
  columnHelper.display({
    id: 'lists',
    header: 'Lists',
    size: 120,
    cell: (info) => {
      const lists = info.row.original.lists;
      if (!lists?.length) return h('span', { class: 'text-xs text-muted-foreground' }, '—');
      const visibleLists = lists.slice(0, 1);
      const hiddenLists = lists.slice(1);

      return h('div', { class: 'flex max-w-[8rem] flex-wrap items-center gap-1.5' }, [
        ...visibleLists.map((list) => h(ListBadge, {
          name: list.name,
          class: 'max-w-[7rem] shrink-0',
        })),
        hiddenLists.length ? h(BadgeOverflowPopover, {
          items: hiddenLists,
          title: 'More lists',
          tone: 'list',
        }) : null,
      ]);
    },
  }),
  columnHelper.display({
    id: 'tags',
    header: 'Tags',
    size: 230,
    cell: (info) => {
      const tags = orderedTags(info.row.original.tags ?? []);
      if (!tags?.length) return h('span', { class: 'text-xs text-muted-foreground' }, '—');
      const visibleTags = tags.slice(0, 2);
      const hiddenTags = tags.slice(2);

      return h('div', { class: 'flex max-w-[14rem] flex-wrap items-center gap-1.5' }, [
        ...visibleTags.map((tag) => h(TagPill, {
          name: tag.name,
          class: 'max-w-[7.5rem] shrink-0',
        })),
        hiddenTags.length ? h(BadgeOverflowPopover, {
          items: hiddenTags,
          title: 'More tags',
          tone: 'tag',
        }) : null,
      ]);
    },
  }),
  columnHelper.accessor('status', {
    header: 'Status',
    size: 90,
    cell: (info) => h(StatusBadge, { status: info.getValue() }),
  }),
  columnHelper.accessor('updated_at', {
    header: 'Last Updated',
    size: 168,
    cell: (info) => renderTimestamp(info.getValue()),
    enableSorting: true,
  }),
  columnHelper.accessor('created_at', {
    header: 'Added',
    size: 168,
    cell: (info) => renderTimestamp(info.getValue()),
    enableSorting: true,
  }),
  columnHelper.display({
    id: 'actions',
    header: '',
    size: 50,
    cell: (info) => {
      const contact = info.row.original;
      return h(DropdownMenu, null, {
        trigger: () => h(Button, { variant: 'ghost', size: 'icon-sm' }, () => h(MoreHorizontal, { class: 'h-4 w-4' })),
        default: () => [
          h(DropdownMenuItem, { onSelect: () => router.get(route('contacts.show', contact.id)) }, () => [
            h(Eye, { class: 'h-4 w-4' }),
            'View',
          ]),
          h(DropdownMenuItem, { onSelect: () => router.get(route('contacts.edit', contact.id)) }, () => [
            h(Pencil, { class: 'h-4 w-4' }),
            'Edit',
          ]),
          h(DropdownMenuSeparator),
          h(DropdownMenuItem, { destructive: true, onSelect: () => deleteContact(contact) }, () => [
            h(Trash2, { class: 'h-4 w-4' }),
            'Delete',
          ]),
        ],
      });
    },
  }),
];
</script>

<template>
  <Head title="Contacts" />

  <AppLayout :breadcrumbs="[{ label: 'Contacts' }]">
    <PageHeader title="Contacts" :subtitle="`${formatNumber(contacts.meta.total)} total contacts`">
      <template #actions>
        <Button variant="outline" @click="exportContacts">
          <Download class="h-4 w-4" />
          Export
        </Button>
        <Link :href="route('imports.create')">
          <Button variant="outline">
            <Upload class="h-4 w-4" />
            Import
          </Button>
        </Link>
        <Link :href="route('contacts.create')">
          <Button>
            <UserPlus class="h-4 w-4" />
            Add Contact
          </Button>
        </Link>
      </template>
    </PageHeader>

    <div class="flex items-center gap-3 mb-4">
      <SearchInput
        :model-value="search"
        @update:model-value="handleSearch"
        placeholder="Search by name, phone, email..."
        class="w-full max-w-sm"
      />
      <Button variant="outline" @click="filtersOpen = true">
        <Filter class="h-4 w-4" />
        Filters
      </Button>
    </div>

    <DataTable
      :columns="columns"
      :data="contacts.data"
      :meta="contacts.meta"
      :selectable="true"
      :manual-sorting="true"
      :sort-by="activeSortBy"
      :sort-dir="activeSortDir"
      empty-title="No contacts found"
      empty-description="Add your first contact or import from a CSV file."
      @sort-change="handleSortChange"
    >
      <template #empty-action>
        <Link :href="route('contacts.create')">
          <Button size="sm">
            <UserPlus class="h-4 w-4" />
            Add Contact
          </Button>
        </Link>
      </template>
      <template #bulk-actions="{ rows, clearSelection }">
        <div class="flex flex-wrap items-center gap-2">
          <Select v-model="bulkTagId" :options="tagOptions" class="w-40" />
          <Button size="sm" variant="outline" :loading="bulkProcessing" :disabled="!bulkTagId" @click="bulkAddTag(rows, clearSelection)">
            <Tag class="h-4 w-4" />
            Add Tag
          </Button>
          <Select v-model="bulkListId" :options="listOptions" class="w-40" />
          <Button size="sm" variant="outline" :loading="bulkProcessing" :disabled="!bulkListId" @click="bulkAddToList(rows, clearSelection)">
            <List class="h-4 w-4" />
            Add to List
          </Button>
          <Button size="sm" variant="destructive" :loading="bulkProcessing" @click="bulkDelete(rows, clearSelection)">Delete</Button>
        </div>
      </template>
    </DataTable>

    <FilterDrawer
      v-model:open="filtersOpen"
      title="Filter Contacts"
      @apply="applyFilters"
      @reset="resetFilters"
    >
      <div class="space-y-4">
        <div class="space-y-1.5">
          <Label>Status</Label>
          <Select v-model="selectedFilters.status" :options="statusOptions" placeholder="All statuses" />
        </div>
        <div class="space-y-1.5">
          <Label>Tag</Label>
          <Select v-model="selectedFilters.tag_id" :options="tagOptions" placeholder="All tags" />
        </div>
        <div class="space-y-1.5">
          <Label>List</Label>
          <Select v-model="selectedFilters.list_id" :options="listOptions" placeholder="All lists" />
        </div>
        <div class="space-y-1.5">
          <Label>Source</Label>
          <Select v-model="selectedFilters.source" :options="sourceFilterOptions" placeholder="All sources" />
        </div>
        <div class="space-y-1.5">
          <Label>City</Label>
          <Input v-model="selectedFilters.city" placeholder="Filter by city" />
        </div>
      </div>
    </FilterDrawer>
  </AppLayout>
</template>
