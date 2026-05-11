<script setup lang="ts">
import { ref } from 'vue';
import AppLayout from '@/Components/layout/AppLayout.vue';
import PageHeader from '@/Components/common/PageHeader.vue';
import SearchInput from '@/Components/common/SearchInput.vue';
import Pagination from '@/Components/common/Pagination.vue';
import Card from '@/Components/ui/Card.vue';
import Badge from '@/Components/ui/Badge.vue';
import Select from '@/Components/ui/Select.vue';
import { Head, router } from '@inertiajs/vue3';
import { Activity, Clock } from 'lucide-vue-next';
import type { ActivityLog, Pagination as PaginationType } from '@/types';
import { formatDateTime, formatRelativeTime, truncate } from '@/lib/utils';

const props = defineProps<{
  activities: { data: ActivityLog[]; meta: PaginationType };
  filters: {
    search?: string;
    event?: string;
  };
}>();

const search = ref(props.filters.search ?? '');
const selectedEvent = ref(props.filters.event ?? '');

const eventOptions = [
  { label: 'All Events', value: '' },
  { label: 'Created', value: 'created' },
  { label: 'Updated', value: 'updated' },
  { label: 'Deleted', value: 'deleted' },
  { label: 'Restored', value: 'restored' },
  { label: 'Sent', value: 'sent' },
  { label: 'Resend Queued', value: 'resend_queued' },
  { label: 'Failed', value: 'failed' },
  { label: 'Imported', value: 'imported' },
  { label: 'Exported', value: 'exported' },
];

const eventBadgeVariant: Record<string, 'default' | 'secondary' | 'success' | 'danger' | 'warning' | 'info' | 'outline'> = {
  created: 'success',
  updated: 'info',
  deleted: 'danger',
  restored: 'warning',
  sent: 'success',
  resend_queued: 'warning',
  failed: 'danger',
  imported: 'info',
  exported: 'secondary',
};

function applyFilters() {
  router.get(
    route('activity-logs.index'),
    {
      search: search.value || undefined,
      event: selectedEvent.value || undefined,
    },
    { preserveState: true },
  );
}

function handleSearch(val: string) {
  search.value = val;
  applyFilters();
}

function handleEventFilter(val: string | number) {
  selectedEvent.value = String(val);
  applyFilters();
}
</script>

<template>
  <Head title="Activity Log" />

  <AppLayout :breadcrumbs="[{ label: 'Activity Log' }]">
    <PageHeader
      title="Activity Log"
      :subtitle="`${activities.meta.total} events recorded`"
    />

    <!-- Filters -->
    <div class="flex flex-col sm:flex-row items-start sm:items-center gap-3 mb-6">
      <SearchInput
        :model-value="search"
        @update:model-value="handleSearch"
        placeholder="Search activity descriptions..."
        class="w-full sm:max-w-sm"
      />
      <Select
        :model-value="selectedEvent"
        :options="eventOptions"
        placeholder="Filter by event"
        class="w-full sm:w-48"
        @update:model-value="handleEventFilter"
      />
    </div>

    <!-- Activity List -->
    <Card>
      <div v-if="activities.data.length === 0" class="py-12 text-center">
        <div class="flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 mx-auto mb-3">
          <Activity class="h-6 w-6 text-muted-foreground" />
        </div>
        <p class="text-sm font-medium text-foreground">No activity found</p>
        <p class="text-xs text-muted mt-1">Activity will appear here as actions are performed.</p>
      </div>

      <div v-else class="divide-y divide-border">
        <div
          v-for="activity in activities.data"
          :key="activity.id"
          class="flex items-start gap-4 px-6 py-4 hover:bg-gray-50/50 transition-colors"
        >
          <!-- Icon -->
          <div class="flex h-9 w-9 items-center justify-center rounded-full bg-gray-100 shrink-0 mt-0.5">
            <Clock class="h-4 w-4 text-muted-foreground" />
          </div>

          <!-- Content -->
          <div class="min-w-0 flex-1">
            <p class="text-sm text-foreground">{{ activity.description }}</p>
            <div class="flex flex-wrap items-center gap-2 mt-1.5">
              <Badge :variant="eventBadgeVariant[activity.event] ?? 'secondary'">
                {{ activity.event }}
              </Badge>
              <span v-if="activity.causer_name" class="text-xs text-muted">
                by {{ activity.causer_name }}
              </span>
              <span v-if="activity.subject_type" class="text-xs text-muted">
                {{ truncate(activity.subject_type.split('\\').pop() ?? '', 30) }}
              </span>
            </div>
          </div>

          <!-- Timestamp -->
          <div class="shrink-0 text-right">
            <p class="text-xs text-muted whitespace-nowrap">
              {{ formatRelativeTime(activity.created_at) }}
            </p>
            <p class="mt-1 text-[11px] text-muted-foreground whitespace-nowrap">
              {{ formatDateTime(activity.created_at) }}
            </p>
          </div>
        </div>
      </div>
    </Card>

    <!-- Pagination -->
    <Pagination :meta="activities.meta" />
  </AppLayout>
</template>
