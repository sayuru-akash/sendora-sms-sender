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
import { Send, Pencil, Trash2, Users } from 'lucide-vue-next';
import type { Tag, Pagination } from '@/types';
import type { Contact } from '@/types/contact';
import type { Campaign } from '@/types/campaign';
import { createColumnHelper } from '@tanstack/vue-table';
import { h } from 'vue';
import { formatNumber, formatDate } from '@/lib/utils';
import { useConfirm } from '@/composables/useConfirm';

const props = defineProps<{
  tag: Tag;
  contacts: { data: Contact[]; meta: Pagination };
  campaigns: Campaign[];
}>();

const { confirm } = useConfirm();

async function deleteTag() {
  const confirmed = await confirm({
    title: 'Delete Tag',
    message: `Delete tag "${props.tag.name}"? This will remove it from all contacts.`,
    confirmLabel: 'Delete',
    variant: 'destructive',
  });
  if (confirmed) {
    router.delete(route('tags.destroy', props.tag.id));
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
  <Head :title="`Tag: ${tag.name}`" />

  <AppLayout :breadcrumbs="[
    { label: 'Tags', href: route('tags.index') },
    { label: tag.name },
  ]">
    <PageHeader :title="tag.name" :subtitle="`${formatNumber(tag.contacts_count)} contacts`">
      <template #actions>
        <Link :href="route('campaigns.builder', { tag_id: tag.id })">
          <Button>
            <Send class="h-4 w-4" />
            Send Campaign
          </Button>
        </Link>
        <Button variant="ghost" @click="deleteTag">
          <Trash2 class="h-4 w-4 text-danger" />
        </Button>
      </template>
    </PageHeader>

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6 mb-6">
      <Card class="lg:col-span-1">
        <CardContent class="pt-6 space-y-3">
          <div class="flex items-center gap-2">
            <div class="h-4 w-4 rounded-full" :style="{ backgroundColor: tag.color }" />
            <span class="text-sm font-medium text-foreground">{{ tag.name }}</span>
          </div>
          <p v-if="tag.description" class="text-sm text-muted">{{ tag.description }}</p>
          <div class="flex items-center gap-1.5 text-sm text-muted">
            <Users class="h-4 w-4" />
            {{ formatNumber(tag.contacts_count) }} contacts
          </div>
        </CardContent>
      </Card>
    </div>

    <DataTable
      :columns="columns"
      :data="contacts.data"
      :meta="contacts.meta"
      empty-title="No contacts with this tag"
    />
  </AppLayout>
</template>
