<script setup lang="ts">
import AppLayout from '@/Components/layout/AppLayout.vue';
import PageHeader from '@/Components/common/PageHeader.vue';
import Card from '@/Components/ui/Card.vue';
import CardContent from '@/Components/ui/CardContent.vue';
import CardHeader from '@/Components/ui/CardHeader.vue';
import CardTitle from '@/Components/ui/CardTitle.vue';
import Button from '@/Components/ui/Button.vue';
import Badge from '@/Components/ui/Badge.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { Pencil, Trash2, Send } from 'lucide-vue-next';
import type { SavedSegment } from '@/types';
import { formatDate } from '@/lib/utils';
import { useConfirm } from '@/composables/useConfirm';

const props = defineProps<{
  segment: SavedSegment;
}>();

const { confirm } = useConfirm();

async function handleDelete() {
  const confirmed = await confirm({
    title: 'Delete Segment',
    message: `Delete saved segment "${props.segment.name}"? This action cannot be undone.`,
    confirmLabel: 'Delete',
    variant: 'destructive',
  });
  if (confirmed) {
    router.delete(route('segments.destroy', props.segment.id));
  }
}

const filterLabels: Record<string, string> = {
  status: 'Status',
  source: 'Source',
  district: 'District',
  city: 'City',
  gender: 'Gender',
  date_from: 'Created From',
  date_to: 'Created To',
  tag_ids: 'Tags',
  list_ids: 'Lists',
};

function formatFilterValue(key: string, value: unknown): string {
  if (Array.isArray(value)) {
    return value.length ? value.join(', ') : '—';
  }
  return value ? String(value) : '—';
}

const activeFilters = Object.entries(props.segment.filters ?? {}).filter(
  ([, value]) => value !== '' && value !== null && value !== undefined && (!Array.isArray(value) || value.length > 0),
);
</script>

<template>
  <Head :title="segment.name" />

  <AppLayout :breadcrumbs="[
    { label: 'Segments', href: route('segments.index') },
    { label: segment.name },
  ]">
    <PageHeader :title="segment.name" :subtitle="segment.description ?? undefined">
      <template #actions>
        <Link :href="route('campaigns.create', { segment_id: segment.id })">
          <Button variant="outline">
            <Send class="h-4 w-4" />
            Send Campaign
          </Button>
        </Link>
        <Link :href="route('segments.edit', segment.id)">
          <Button variant="outline">
            <Pencil class="h-4 w-4" />
            Edit
          </Button>
        </Link>
        <Button variant="ghost" @click="handleDelete">
          <Trash2 class="h-4 w-4 text-danger" />
        </Button>
      </template>
    </PageHeader>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Segment Info -->
      <Card>
        <CardHeader>
          <CardTitle>Details</CardTitle>
        </CardHeader>
        <CardContent>
          <dl class="space-y-3 text-sm">
            <div class="flex justify-between">
              <dt class="text-muted">Name</dt>
              <dd class="font-medium text-foreground">{{ segment.name }}</dd>
            </div>
            <div v-if="segment.description" class="flex justify-between">
              <dt class="text-muted">Description</dt>
              <dd class="font-medium text-foreground text-right max-w-[60%]">{{ segment.description }}</dd>
            </div>
            <div class="flex justify-between">
              <dt class="text-muted">Created By</dt>
              <dd class="font-medium text-foreground">{{ segment.creator?.name ?? '—' }}</dd>
            </div>
            <div class="flex justify-between">
              <dt class="text-muted">Created</dt>
              <dd class="font-medium text-foreground">{{ formatDate(segment.created_at) }}</dd>
            </div>
            <div class="flex justify-between">
              <dt class="text-muted">Last Updated</dt>
              <dd class="font-medium text-foreground">{{ formatDate(segment.updated_at) }}</dd>
            </div>
          </dl>
        </CardContent>
      </Card>

      <!-- Filter Criteria -->
      <Card class="lg:col-span-2">
        <CardHeader>
          <CardTitle>Filter Criteria</CardTitle>
        </CardHeader>
        <CardContent>
          <div v-if="activeFilters.length === 0" class="text-sm text-muted py-4 text-center">
            No filter criteria defined.
          </div>
          <div v-else class="space-y-3">
            <div
              v-for="[key, value] in activeFilters"
              :key="key"
              class="flex items-center justify-between rounded-lg border border-border bg-gray-50 px-4 py-3"
            >
              <span class="text-sm font-medium text-foreground">{{ filterLabels[key] ?? key }}</span>
              <Badge variant="outline">{{ formatFilterValue(key, value) }}</Badge>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
