<script setup lang="ts">
import AppLayout from '@/Components/layout/AppLayout.vue';
import PageHeader from '@/Components/common/PageHeader.vue';
import Card from '@/Components/ui/Card.vue';
import CardContent from '@/Components/ui/CardContent.vue';
import Button from '@/Components/ui/Button.vue';
import StatusBadge from '@/Components/common/StatusBadge.vue';
import DataTable from '@/Components/common/DataTable.vue';
import Avatar from '@/Components/ui/Avatar.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { Send, Trash2, Users, Upload, Download } from 'lucide-vue-next';
import type { ListModel, Pagination } from '@/types';
import type { Contact } from '@/types/contact';
import type { Campaign } from '@/types/campaign';
import { createColumnHelper } from '@tanstack/vue-table';
import { computed, h } from 'vue';
import { formatNumber, formatDate } from '@/lib/utils';
import { useConfirm } from '@/composables/useConfirm';

const props = defineProps<{
  list: ListModel;
  contacts?: { data: Contact[]; meta: Pagination };
  campaigns?: Campaign[];
}>();

const emptyMeta: Pagination = {
  current_page: 1,
  last_page: 1,
  per_page: 25,
  total: 0,
  from: null,
  to: null,
};

const safeContacts = computed(() => props.contacts ?? { data: [], meta: emptyMeta });
const { confirm } = useConfirm();

async function deleteList() {
  const confirmed = await confirm({
    title: 'Delete List',
    message: `Delete list "${props.list.name}"? Contacts will not be deleted.`,
    confirmLabel: 'Delete',
    variant: 'destructive',
  });
  if (confirmed) {
    router.delete(route('lists.destroy', props.list.id));
  }
}

const columnHelper = createColumnHelper<Contact>();

const columns = [
  columnHelper.accessor('full_name', {
    header: 'Name',
    cell: (info) => {
      const c = info.row.original;
      return h('div', { class: 'flex items-center gap-3' }, [
        h(Avatar, { alt: c.full_name, size: 'sm' }),
        h('div', {}, [
          h('p', { class: 'text-sm font-medium text-foreground' }, c.full_name),
          c.email ? h('p', { class: 'text-xs text-muted' }, c.email) : null,
        ]),
      ]);
    },
  }),
  columnHelper.accessor('phone', { header: 'Phone' }),
  columnHelper.accessor('company', { header: 'Company', cell: (info) => h('span', { class: 'text-muted' }, info.getValue() ?? '—') }),
  columnHelper.accessor('status', { header: 'Status', cell: (info) => h(StatusBadge, { status: info.getValue() }) }),
];
</script>

<template>
  <Head :title="list.name" />

  <AppLayout :breadcrumbs="[
    { label: 'Lists', href: route('lists.index') },
    { label: list.name },
  ]">
    <PageHeader :title="list.name" :subtitle="`${formatNumber(list.contacts_count)} contacts`">
      <template #actions>
        <Link :href="route('campaigns.builder', { list_id: list.id })">
          <Button>
            <Send class="h-4 w-4" />
            Send Campaign
          </Button>
        </Link>
        <Link :href="route('imports.create', { list_id: list.id })">
          <Button variant="outline">
            <Upload class="h-4 w-4" />
            Import Into List
          </Button>
        </Link>
        <Button variant="ghost" @click="deleteList">
          <Trash2 class="h-4 w-4 text-danger" />
        </Button>
      </template>
    </PageHeader>

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6 mb-6">
      <Card class="lg:col-span-1">
        <CardContent class="pt-6 space-y-3">
          <div class="flex items-center gap-2">
            <div class="h-4 w-4 rounded-full" :style="{ backgroundColor: list.color }" />
            <span class="text-sm font-medium text-foreground">{{ list.name }}</span>
          </div>
          <p v-if="list.description" class="text-sm text-muted">{{ list.description }}</p>
          <div class="flex items-center gap-1.5 text-sm text-muted">
            <Users class="h-4 w-4" />
            {{ formatNumber(list.contacts_count) }} contacts
          </div>
          <StatusBadge :status="list.status" />
        </CardContent>
      </Card>
    </div>

    <DataTable
      :columns="columns"
      :data="safeContacts.data"
      :meta="safeContacts.meta"
      empty-title="No contacts in this list"
    />
  </AppLayout>
</template>
