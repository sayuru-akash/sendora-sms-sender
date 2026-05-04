<script setup lang="ts">
import { ref, computed } from 'vue';
import AppLayout from '@/Components/layout/AppLayout.vue';
import PageHeader from '@/Components/common/PageHeader.vue';
import SearchInput from '@/Components/common/SearchInput.vue';
import StatusBadge from '@/Components/common/StatusBadge.vue';
import TagPill from '@/Components/common/TagPill.vue';
import ListBadge from '@/Components/common/ListBadge.vue';
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
import { formatNumber } from '@/lib/utils';
import { useConfirm } from '@/composables/useConfirm';

const props = defineProps<{
  contacts: { data: Contact[]; meta: Pagination };
  tags: TagType[];
  lists: ListModel[];
  filters: Record<string, string | undefined>;
}>();

const { confirm } = useConfirm();
const search = ref(props.filters.search ?? '');
const filtersOpen = ref(false);
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

const sourceOptions = [
  { label: 'All', value: '' },
  { label: 'Manual', value: 'manual' },
  { label: 'Import', value: 'import' },
  { label: 'API', value: 'api' },
  { label: 'Web', value: 'web' },
  { label: 'Referral', value: 'referral' },
];

const tagOptions = computed(() => [
  { label: 'All', value: '' },
  ...props.tags.map((t) => ({ label: t.name, value: String(t.id) })),
]);

const listOptions = computed(() => [
  { label: 'All', value: '' },
  ...props.lists.map((l) => ({ label: l.name, value: String(l.id) })),
]);

function applyFilters() {
  router.get(route('contacts.index'), {
    search: search.value || undefined,
    ...Object.fromEntries(
      Object.entries(selectedFilters.value).filter(([, v]) => v !== '')
    ),
  }, { preserveState: true });
}

function resetFilters() {
  selectedFilters.value = { status: '', tag_id: '', list_id: '', source: '', city: '' };
  applyFilters();
}

function handleSearch(val: string) {
  search.value = val;
  applyFilters();
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

const columnHelper = createColumnHelper<Contact>();

const columns = [
  columnHelper.accessor('full_name', {
    header: 'Name',
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
    cell: (info) => h('span', { class: 'text-sm text-foreground' }, info.getValue()),
  }),
  columnHelper.accessor('company', {
    header: 'Company',
    cell: (info) => h('span', { class: 'text-sm text-muted' }, info.getValue() ?? '—'),
  }),
  columnHelper.display({
    id: 'lists',
    header: 'Lists',
    cell: (info) => {
      const lists = info.row.original.lists;
      if (!lists?.length) return h('span', { class: 'text-xs text-muted-foreground' }, '—');
      return h('div', { class: 'flex flex-wrap gap-1' },
        lists.slice(0, 2).map((l) => h(ListBadge, { name: l.name }))
      );
    },
  }),
  columnHelper.display({
    id: 'tags',
    header: 'Tags',
    cell: (info) => {
      const tags = info.row.original.tags;
      if (!tags?.length) return h('span', { class: 'text-xs text-muted-foreground' }, '—');
      return h('div', { class: 'flex flex-wrap gap-1' },
        tags.slice(0, 2).map((t) => h(TagPill, { name: t.name }))
      );
    },
  }),
  columnHelper.accessor('status', {
    header: 'Status',
    cell: (info) => h(StatusBadge, { status: info.getValue() }),
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
        <Link :href="route('imports.upload')">
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
      empty-title="No contacts found"
      empty-description="Add your first contact or import from a CSV file."
    >
      <template #empty-action>
        <Link :href="route('contacts.create')">
          <Button size="sm">
            <UserPlus class="h-4 w-4" />
            Add Contact
          </Button>
        </Link>
      </template>
      <template #bulk-actions="{ rows }">
        <div class="flex items-center gap-2">
          <Button size="sm" variant="outline">Add Tag</Button>
          <Button size="sm" variant="outline">Add to List</Button>
          <Button size="sm" variant="destructive">Delete</Button>
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
          <Select v-model="selectedFilters.source" :options="sourceOptions" placeholder="All sources" />
        </div>
        <div class="space-y-1.5">
          <Label>City</Label>
          <Input v-model="selectedFilters.city" placeholder="Filter by city" />
        </div>
      </div>
    </FilterDrawer>
  </AppLayout>
</template>
