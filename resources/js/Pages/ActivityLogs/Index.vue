<script setup lang="ts">
import { computed, ref } from 'vue';
import AppLayout from '@/Components/layout/AppLayout.vue';
import PageHeader from '@/Components/common/PageHeader.vue';
import SearchInput from '@/Components/common/SearchInput.vue';
import Pagination from '@/Components/common/Pagination.vue';
import Card from '@/Components/ui/Card.vue';
import Button from '@/Components/ui/Button.vue';
import Badge from '@/Components/ui/Badge.vue';
import Select from '@/Components/ui/Select.vue';
import Sheet from '@/Components/ui/Sheet.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { Activity, ArrowUpRight, Clock, Eye, FilterX, X } from 'lucide-vue-next';
import type { ActivityLog, Pagination as PaginationType } from '@/types';
import { formatDateTime, formatRelativeTime, truncate } from '@/lib/utils';

type ActivityCauser = {
  id: number;
  name: string;
  email: string;
};

const props = defineProps<{
  activities: { data: ActivityLog[]; meta: PaginationType };
  filters: {
    search?: string;
    event?: string;
    log_name?: string;
    causer_id?: string | number;
    subject_type?: string;
    subject_id?: string | number;
    per_page?: string | number;
  };
  filterOptions: {
    events: string[];
    logNames: string[];
    subjectTypes: string[];
    causers: ActivityCauser[];
    perPage: number[];
  };
}>();

const search = ref(props.filters.search ?? '');
const selectedEvent = ref(props.filters.event ?? '');
const selectedLogName = ref(props.filters.log_name ?? '');
const selectedSubjectType = ref(props.filters.subject_type ?? '');
const selectedCauserId = ref(props.filters.causer_id ? String(props.filters.causer_id) : '');
const selectedPerPage = ref(props.filters.per_page ? String(props.filters.per_page) : String(props.activities.meta.per_page ?? 25));
const openActivityId = ref<number | null>(null);
const detailsOpen = computed({
  get: () => openActivityId.value !== null,
  set: (open: boolean) => {
    if (!open) {
      openActivityId.value = null;
    }
  },
});
const selectedActivity = computed(() => props.activities.data.find((activity) => activity.id === openActivityId.value) ?? null);
const hasScopedFilters = computed(() => Boolean(props.filters.subject_type || props.filters.subject_id || props.filters.causer_id || props.filters.log_name));
const hasAnyFilters = computed(() => Boolean(search.value || selectedEvent.value || selectedLogName.value || selectedSubjectType.value || selectedCauserId.value || hasScopedFilters.value));

const eventOptions = computed(() => [
  { label: 'All Events', value: '' },
  ...props.filterOptions.events.map((event) => ({ label: formatEventLabel(event), value: event })),
]);

const logNameOptions = computed(() => [
  { label: 'All Logs', value: '' },
  ...props.filterOptions.logNames.map((logName) => ({ label: formatEventLabel(logName), value: logName })),
]);

const subjectTypeOptions = computed(() => [
  { label: 'All Records', value: '' },
  ...props.filterOptions.subjectTypes.map((subjectType) => ({ label: subjectTypeLabel(subjectType), value: subjectType })),
]);

const causerOptions = computed(() => [
  { label: 'All Actors', value: '' },
  ...props.filterOptions.causers.map((causer) => ({ label: `${causer.name} · ${causer.email}`, value: String(causer.id) })),
]);

const perPageOptions = computed(() => props.filterOptions.perPage.map((value) => ({ label: `${value} / page`, value: String(value) })));

const scopedFilterChips = computed(() => {
  const chips: { key: string; label: string; value: string }[] = [];

  if (props.filters.log_name) {
    chips.push({ key: 'log_name', label: 'Log', value: formatEventLabel(String(props.filters.log_name)) });
  }

  if (props.filters.causer_id) {
    const causer = props.filterOptions.causers.find((item) => String(item.id) === String(props.filters.causer_id));
    chips.push({ key: 'causer_id', label: 'Actor', value: causer?.name ?? `User #${props.filters.causer_id}` });
  }

  if (props.filters.subject_type) {
    const value = props.filters.subject_id
      ? `${subjectTypeLabel(String(props.filters.subject_type))} #${props.filters.subject_id}`
      : subjectTypeLabel(String(props.filters.subject_type));
    chips.push({ key: 'subject_type', label: 'Record', value });
  }

  return chips;
});

const eventBadgeVariant: Record<string, 'default' | 'secondary' | 'success' | 'danger' | 'warning' | 'info' | 'outline'> = {
  created: 'success',
  updated: 'info',
  deleted: 'danger',
  restored: 'warning',
  sent: 'success',
  send_requested: 'info',
  recipient_sent: 'success',
  recipient_failed: 'danger',
  queued: 'info',
  sending: 'warning',
  completed: 'success',
  resend_queued: 'warning',
  paused: 'warning',
  resumed: 'info',
  cancelled: 'danger',
  failed: 'danger',
  imported: 'info',
  exported: 'secondary',
};

function cleanFilters(filters: Record<string, string | number | undefined | null>) {
  return Object.fromEntries(
    Object.entries(filters).filter(([, value]) => value !== undefined && value !== null && value !== ''),
  );
}

function applyFilters(overrides: Record<string, string | number | undefined | null> = {}) {
  router.get(
    route('activity-logs.index'),
    cleanFilters({
      search: search.value || undefined,
      event: selectedEvent.value || undefined,
      log_name: selectedLogName.value || undefined,
      causer_id: selectedCauserId.value || undefined,
      subject_type: selectedSubjectType.value || undefined,
      subject_id: props.filters.subject_id || undefined,
      per_page: selectedPerPage.value || undefined,
      ...overrides,
    }),
    { preserveState: true, preserveScroll: true, replace: true },
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

function handleLogNameFilter(val: string | number) {
  selectedLogName.value = String(val);
  applyFilters();
}

function handleSubjectTypeFilter(val: string | number) {
  selectedSubjectType.value = String(val);
  applyFilters({ subject_id: undefined });
}

function handleCauserFilter(val: string | number) {
  selectedCauserId.value = String(val);
  applyFilters();
}

function handlePerPageFilter(val: string | number) {
  selectedPerPage.value = String(val);
  applyFilters({ page: undefined });
}

function propertyEntries(properties: Record<string, unknown>) {
  return Object.entries(properties ?? {}).filter(([key, value]) => !['attributes', 'old'].includes(key) && value !== null && value !== undefined && value !== '');
}

function changeEntries(properties: Record<string, unknown>) {
  const attributes = properties.attributes;
  const old = properties.old;

  if (!attributes || typeof attributes !== 'object' || Array.isArray(attributes)) {
    return [];
  }

  const previousValues = old && typeof old === 'object' && !Array.isArray(old) ? old as Record<string, unknown> : {};

  return Object.entries(attributes as Record<string, unknown>).map(([key, value]) => ({
    key,
    old: previousValues[key],
    value,
  }));
}

function formatPropertyValue(value: unknown): string {
  if (Array.isArray(value) || (typeof value === 'object' && value !== null)) {
    return JSON.stringify(value);
  }

  return String(value);
}

function formatEventLabel(event: string): string {
  return event
    .split('_')
    .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
    .join(' ');
}

function subjectTypeLabel(subjectType: string): string {
  return subjectType.split('\\').pop() ?? subjectType;
}

function openActivityDetails(activityId: number) {
  openActivityId.value = activityId;
}

function clearScopedFilter(key: string) {
  if (key === 'log_name') {
    selectedLogName.value = '';
    applyFilters({ log_name: undefined });
    return;
  }

  if (key === 'causer_id') {
    selectedCauserId.value = '';
    applyFilters({ causer_id: undefined });
    return;
  }

  if (key === 'subject_type') {
    selectedSubjectType.value = '';
    applyFilters({ subject_type: undefined, subject_id: undefined });
  }
}

function clearFilters() {
  search.value = '';
  selectedEvent.value = '';
  selectedLogName.value = '';
  selectedSubjectType.value = '';
  selectedCauserId.value = '';
  selectedPerPage.value = '25';
  router.get(route('activity-logs.index'), {}, { preserveState: true, preserveScroll: true, replace: true });
}
</script>

<template>
  <Head title="Activity Log" />

  <AppLayout :breadcrumbs="[{ label: 'Activity Log' }]">
    <PageHeader
      title="Activity Log"
      :subtitle="`${activities.meta.total} events recorded`"
    />

    <div class="mb-6 space-y-3">
      <div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-6">
      <SearchInput
        :model-value="search"
        @update:model-value="handleSearch"
          placeholder="Search logs..."
          class="xl:col-span-2"
      />
      <Select
        :model-value="selectedEvent"
        :options="eventOptions"
        @update:model-value="handleEventFilter"
      />
        <Select
          :model-value="selectedLogName"
          :options="logNameOptions"
          @update:model-value="handleLogNameFilter"
        />
        <Select
          :model-value="selectedSubjectType"
          :options="subjectTypeOptions"
          @update:model-value="handleSubjectTypeFilter"
        />
        <Select
          :model-value="selectedCauserId"
          :options="causerOptions"
          @update:model-value="handleCauserFilter"
        />
      </div>

      <div class="flex flex-wrap items-center gap-2">
        <Select
          :model-value="selectedPerPage"
          :options="perPageOptions"
          class="w-32"
          @update:model-value="handlePerPageFilter"
        />
        <span
          v-for="chip in scopedFilterChips"
          :key="chip.key"
          class="inline-flex items-center gap-1 rounded-full border border-border bg-white px-2.5 py-1 text-xs text-foreground shadow-xs"
        >
          <span class="text-muted">{{ chip.label }}</span>
          {{ chip.value }}
          <button
            type="button"
            class="rounded-full p-0.5 text-muted-foreground transition-colors hover:bg-gray-100 hover:text-foreground"
            :aria-label="`Clear ${chip.label} filter`"
            @click="clearScopedFilter(chip.key)"
          >
            <X class="h-3 w-3" />
          </button>
        </span>
      <Button
        v-if="hasAnyFilters"
        variant="ghost"
        size="sm"
        class="text-muted-foreground"
        @click="clearFilters"
      >
          <FilterX class="h-3.5 w-3.5" />
        Clear
      </Button>
      </div>
    </div>

    <Card>
      <div v-if="activities.data.length === 0" class="py-12 text-center">
        <div class="flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 mx-auto mb-3">
          <Activity class="h-6 w-6 text-muted-foreground" />
        </div>
        <p class="text-sm font-medium text-foreground">No activity found</p>
        <p class="text-xs text-muted mt-1">
          {{ hasAnyFilters ? 'No logs match these filters.' : 'Activity will appear as actions are performed.' }}
        </p>
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
                {{ formatEventLabel(activity.event) }}
              </Badge>
              <span v-if="activity.causer_name" class="text-xs text-muted">
                by {{ activity.causer_name }}
              </span>
              <span v-if="activity.subject_type" class="text-xs text-muted">
                {{ truncate(activity.subject_type.split('\\').pop() ?? '', 30) }}
                <span v-if="activity.subject_name"> · {{ truncate(activity.subject_name, 40) }}</span>
              </span>
            </div>
          </div>

          <div class="shrink-0 text-right">
            <div class="mb-2 flex justify-end gap-2">
              <Button
                size="sm"
                variant="outline"
                @click="openActivityDetails(activity.id)"
              >
                <Eye class="h-3.5 w-3.5" />
                View
              </Button>
              <Link v-if="activity.subject_url" :href="activity.subject_url" class="inline-flex">
                <Button size="sm" variant="outline">
                  <ArrowUpRight class="h-3.5 w-3.5" />
                  {{ activity.subject_action_label ?? 'Open record' }}
                </Button>
              </Link>
            </div>
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

    <Sheet
      v-model:open="detailsOpen"
      title="Activity Details"
      :description="selectedActivity ? formatDateTime(selectedActivity.created_at) : ''"
      content-class="w-full sm:max-w-xl"
    >
      <div v-if="selectedActivity" class="space-y-6">
        <div class="space-y-3">
          <div class="flex flex-wrap items-center gap-2">
            <Badge :variant="eventBadgeVariant[selectedActivity.event] ?? 'secondary'">
              {{ formatEventLabel(selectedActivity.event) }}
            </Badge>
            <span class="text-sm text-muted">{{ formatRelativeTime(selectedActivity.created_at) }}</span>
          </div>
          <p class="text-sm text-foreground">{{ selectedActivity.description }}</p>
        </div>

        <dl class="grid grid-cols-1 gap-3 text-sm sm:grid-cols-2">
          <div>
            <dt class="text-xs uppercase tracking-wide text-muted">Actor</dt>
            <dd class="mt-1 text-foreground">
              {{ selectedActivity.causer_name ?? 'System' }}
            </dd>
          </div>
          <div>
            <dt class="text-xs uppercase tracking-wide text-muted">Record</dt>
            <dd class="mt-1 text-foreground">
              {{ selectedActivity.subject_type ? subjectTypeLabel(selectedActivity.subject_type) : 'None' }}
              <span v-if="selectedActivity.subject_id" class="text-muted">#{{ selectedActivity.subject_id }}</span>
            </dd>
          </div>
          <div>
            <dt class="text-xs uppercase tracking-wide text-muted">Log</dt>
            <dd class="mt-1 text-foreground">{{ selectedActivity.event }}</dd>
          </div>
          <div>
            <dt class="text-xs uppercase tracking-wide text-muted">Time</dt>
            <dd class="mt-1 text-foreground">{{ formatDateTime(selectedActivity.created_at) }}</dd>
          </div>
        </dl>

        <div v-if="changeEntries(selectedActivity.properties).length" class="space-y-2">
          <h3 class="text-sm font-medium text-foreground">Changes</h3>
          <div class="overflow-hidden rounded-lg border border-border">
            <div
              v-for="change in changeEntries(selectedActivity.properties)"
              :key="change.key"
              class="grid grid-cols-1 gap-2 border-b border-border p-3 last:border-b-0 sm:grid-cols-[120px_1fr]"
            >
              <div class="text-xs font-medium uppercase tracking-wide text-muted">{{ change.key.replaceAll('_', ' ') }}</div>
              <div class="min-w-0 space-y-1 text-xs">
                <p class="break-words text-muted">Old: {{ formatPropertyValue(change.old ?? '—') }}</p>
                <p class="break-words text-foreground">New: {{ formatPropertyValue(change.value) }}</p>
              </div>
            </div>
          </div>
        </div>

        <div v-if="propertyEntries(selectedActivity.properties).length" class="space-y-2">
          <h3 class="text-sm font-medium text-foreground">Details</h3>
          <dl class="grid grid-cols-1 gap-2 rounded-lg border border-border bg-gray-50/50 p-3">
            <div
              v-for="[key, value] in propertyEntries(selectedActivity.properties)"
              :key="key"
              class="min-w-0"
            >
              <dt class="text-[11px] uppercase tracking-wide text-muted">{{ key.replaceAll('_', ' ') }}</dt>
              <dd class="mt-0.5 break-words text-xs text-foreground">{{ formatPropertyValue(value) }}</dd>
            </div>
          </dl>
        </div>

        <Link v-if="selectedActivity.subject_url" :href="selectedActivity.subject_url" class="inline-flex">
          <Button variant="outline">
            <ArrowUpRight class="h-4 w-4" />
            {{ selectedActivity.subject_action_label ?? 'Open record' }}
          </Button>
        </Link>
      </div>
    </Sheet>
  </AppLayout>
</template>
