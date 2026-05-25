<script setup lang="ts">
import { computed } from 'vue';
import AppLayout from '@/Components/layout/AppLayout.vue';
import PageHeader from '@/Components/common/PageHeader.vue';
import Pagination from '@/Components/common/Pagination.vue';
import EmptyState from '@/Components/common/EmptyState.vue';
import Button from '@/Components/ui/Button.vue';
import DropdownMenu from '@/Components/ui/DropdownMenu.vue';
import DropdownMenuItem from '@/Components/ui/DropdownMenuItem.vue';
import DropdownMenuSeparator from '@/Components/ui/DropdownMenuSeparator.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { Filter, Plus, MoreHorizontal, Eye, Pencil, Trash2, Send } from 'lucide-vue-next';
import type { SavedSegment, Pagination as PaginationType } from '@/types';
import { formatDate, formatNumber } from '@/lib/utils';
import { useConfirm } from '@/composables/useConfirm';

const props = defineProps<{
  segments?: { data: SavedSegment[]; meta: PaginationType };
  filters?: Record<string, string | undefined>;
}>();

const emptyMeta: PaginationType = {
  current_page: 1,
  last_page: 1,
  per_page: 25,
  total: 0,
  from: null,
  to: null,
};

const safeSegments = computed(() => props.segments ?? { data: [], meta: emptyMeta });
const { confirm } = useConfirm();

async function deleteSegment(segment: SavedSegment) {
  const confirmed = await confirm({
    title: 'Delete Segment',
    message: `Delete saved segment "${segment.name}"? This action cannot be undone.`,
    confirmLabel: 'Delete',
    variant: 'destructive',
  });
  if (confirmed) {
    router.delete(route('segments.destroy', segment.id));
  }
}

function formatFiltersSummary(filters: SavedSegment['filters']): string {
  const parts: string[] = [];
  if (filters.status) parts.push(`Status: ${filters.status}`);
  if (filters.source) parts.push(`Source: ${filters.source}`);
  if (filters.city) parts.push(`City: ${filters.city}`);
  if (filters.district) parts.push(`District: ${filters.district}`);
  if (filters.gender) parts.push(`Gender: ${filters.gender}`);
  if (filters.tag_ids?.length) parts.push(`${filters.tag_ids.length} tag(s)`);
  if (filters.list_ids?.length) parts.push(`${filters.list_ids.length} list(s)`);
  if (filters.date_from) parts.push(`From: ${filters.date_from}`);
  if (filters.date_to) parts.push(`To: ${filters.date_to}`);
  return parts.length ? parts.join(' · ') : 'No filters';
}
</script>

<template>
  <Head title="Saved Segments" />

  <AppLayout :breadcrumbs="[{ label: 'Segments' }]">
    <PageHeader title="Saved Segments" :subtitle="`${formatNumber(safeSegments.meta.total)} segments`">
      <template #actions>
        <Link :href="route('segments.create')">
          <Button>
            <Plus class="h-4 w-4" />
            Create Segment
          </Button>
        </Link>
      </template>
    </PageHeader>

    <div v-if="safeSegments.data.length === 0" class="py-8">
      <EmptyState
        :icon="Filter"
        title="No saved segments"
        description="Create segments to save reusable contact filters for your campaigns."
      >
        <template #action>
          <Link :href="route('segments.create')">
            <Button size="sm">
              <Plus class="h-4 w-4" />
              Create Segment
            </Button>
          </Link>
        </template>
      </EmptyState>
    </div>

    <div v-else class="rounded-xl border border-border bg-white overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="border-b border-border">
              <th class="h-10 px-4 text-left font-medium text-muted text-xs uppercase tracking-wider">Name</th>
              <th class="h-10 px-4 text-left font-medium text-muted text-xs uppercase tracking-wider">Description</th>
              <th class="h-10 px-4 text-left font-medium text-muted text-xs uppercase tracking-wider">Filters</th>
              <th class="h-10 px-4 text-left font-medium text-muted text-xs uppercase tracking-wider">Created By</th>
              <th class="h-10 px-4 text-left font-medium text-muted text-xs uppercase tracking-wider">Created</th>
              <th class="h-10 px-4 w-10"></th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="segment in safeSegments.data"
              :key="segment.id"
              class="border-b border-border hover:bg-gray-50/50 transition-colors"
            >
              <td class="px-4 py-3">
                <Link :href="route('segments.show', segment.id)" class="text-sm font-medium text-foreground hover:text-primary transition-colors">
                  {{ segment.name }}
                </Link>
              </td>
              <td class="px-4 py-3 text-sm text-muted max-w-xs truncate">
                {{ segment.description ?? '—' }}
              </td>
              <td class="px-4 py-3 text-xs text-muted max-w-xs truncate">
                {{ formatFiltersSummary(segment.filters) }}
              </td>
              <td class="px-4 py-3 text-sm text-muted">
                {{ segment.creator?.name ?? '—' }}
              </td>
              <td class="px-4 py-3 text-sm text-muted">
                {{ formatDate(segment.created_at) }}
              </td>
              <td class="px-4 py-3">
                <DropdownMenu>
                  <template #trigger>
                    <Button variant="ghost" size="icon-sm">
                      <MoreHorizontal class="h-4 w-4" />
                    </Button>
                  </template>
                  <DropdownMenuItem @select="router.get(route('segments.show', segment.id))">
                    <Eye class="h-4 w-4" /> View
                  </DropdownMenuItem>
                  <DropdownMenuItem @select="router.get(route('segments.edit', segment.id))">
                    <Pencil class="h-4 w-4" /> Edit
                  </DropdownMenuItem>
                  <DropdownMenuItem @select="router.get(route('campaigns.create', { segment_id: segment.id }))">
                    <Send class="h-4 w-4" /> Send Campaign
                  </DropdownMenuItem>
                  <DropdownMenuSeparator />
                  <DropdownMenuItem destructive @select="deleteSegment(segment)">
                    <Trash2 class="h-4 w-4" /> Delete
                  </DropdownMenuItem>
                </DropdownMenu>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <Pagination :meta="safeSegments.meta" />
    </div>
  </AppLayout>
</template>
