<script setup lang="ts">
import { computed, ref } from 'vue';
import AppLayout from '@/Components/layout/AppLayout.vue';
import PageHeader from '@/Components/common/PageHeader.vue';
import StatusBadge from '@/Components/common/StatusBadge.vue';
import BadgeOverflowPopover from '@/Components/common/BadgeOverflowPopover.vue';
import Pagination from '@/Components/common/Pagination.vue';
import Button from '@/Components/ui/Button.vue';
import Input from '@/Components/ui/Input.vue';
import Select from '@/Components/ui/Select.vue';
import Progress from '@/Components/ui/Progress.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { Upload, FileUp, Eye, Search, X } from 'lucide-vue-next';
import type { Import } from '@/types/import';
import type { Pagination as PaginationType } from '@/types';
import { formatDate, formatNumber } from '@/lib/utils';

const props = defineProps<{
  imports?: { data: Import[]; meta: PaginationType };
  filters?: {
    search?: string;
    status?: string;
    per_page?: string | number;
  };
  summary?: {
    total: number;
    processing: number;
    completed: number;
    failed: number;
  };
}>();

const emptyMeta: PaginationType = {
  current_page: 1,
  last_page: 1,
  per_page: 25,
  total: 0,
  from: null,
  to: null,
};
const emptySummary = {
  total: 0,
  processing: 0,
  completed: 0,
  failed: 0,
};

const safeImports = computed(() => props.imports ?? { data: [], meta: emptyMeta });
const summaryData = computed(() => props.summary ?? emptySummary);
const search = ref(props.filters?.search ?? '');
const status = ref(props.filters?.status ?? '');

const statusOptions = [
  { label: 'All statuses', value: '' },
  { label: 'Uploaded', value: 'uploaded' },
  { label: 'Pending', value: 'pending' },
  { label: 'Processing', value: 'processing' },
  { label: 'Completed', value: 'completed' },
  { label: 'Failed', value: 'failed' },
];

function applyFilters() {
  router.get(route('imports.index'), {
    search: search.value || undefined,
    status: status.value || undefined,
  }, {
    preserveState: true,
    preserveScroll: true,
    replace: true,
  });
}

function clearFilters() {
  search.value = '';
  status.value = '';
  applyFilters();
}

function firstItems(items?: Array<{ name: string }>) {
  return (items ?? []).slice(0, 2);
}

function remainingItems(items?: Array<{ name: string }>) {
  return (items ?? []).slice(2);
}
</script>

<template>
  <Head title="Imports" />

  <AppLayout :breadcrumbs="[{ label: 'Imports' }]">
    <PageHeader title="Imports" subtitle="Manage your contact imports">
      <template #actions>
        <Link :href="route('imports.create')">
          <Button>
            <Upload class="h-4 w-4" />
            Upload File
          </Button>
        </Link>
      </template>
    </PageHeader>

    <div class="mb-4 grid grid-cols-2 gap-3 lg:grid-cols-4">
      <div class="rounded-xl border border-border bg-white p-4">
        <p class="text-xs font-medium uppercase tracking-wide text-muted">Total</p>
        <p class="mt-1 text-2xl font-semibold text-foreground">{{ formatNumber(summaryData.total) }}</p>
      </div>
      <div class="rounded-xl border border-border bg-white p-4">
        <p class="text-xs font-medium uppercase tracking-wide text-muted">In Progress</p>
        <p class="mt-1 text-2xl font-semibold text-warning">{{ formatNumber(summaryData.processing) }}</p>
      </div>
      <div class="rounded-xl border border-border bg-white p-4">
        <p class="text-xs font-medium uppercase tracking-wide text-muted">Completed</p>
        <p class="mt-1 text-2xl font-semibold text-success">{{ formatNumber(summaryData.completed) }}</p>
      </div>
      <div class="rounded-xl border border-border bg-white p-4">
        <p class="text-xs font-medium uppercase tracking-wide text-muted">Failed</p>
        <p class="mt-1 text-2xl font-semibold text-danger">{{ formatNumber(summaryData.failed) }}</p>
      </div>
    </div>

    <div class="mb-4 flex flex-col gap-3 rounded-xl border border-border bg-white p-3 sm:flex-row sm:items-center">
      <div class="relative min-w-0 flex-1">
        <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
        <Input
          v-model="search"
          placeholder="Search files..."
          class="pl-9"
          @keyup.enter="applyFilters"
        />
      </div>
      <Select v-model="status" :options="statusOptions" class="sm:w-44" @change="applyFilters" />
      <Button variant="outline" @click="applyFilters">Apply</Button>
      <Button v-if="search || status" variant="ghost" @click="clearFilters">
        <X class="h-4 w-4" />
        Clear
      </Button>
    </div>

    <div class="overflow-hidden rounded-xl border border-border bg-white">
      <div class="overflow-x-auto">
      <table class="w-full min-w-[980px] text-sm">
        <thead>
          <tr class="border-b border-border">
            <th class="h-10 px-4 text-left font-medium text-muted text-xs uppercase tracking-wider">File</th>
            <th class="h-10 px-4 text-left font-medium text-muted text-xs uppercase tracking-wider">Status</th>
            <th class="h-10 px-4 text-left font-medium text-muted text-xs uppercase tracking-wider">Progress</th>
            <th class="h-10 px-4 text-left font-medium text-muted text-xs uppercase tracking-wider">Rows</th>
            <th class="h-10 px-4 text-left font-medium text-muted text-xs uppercase tracking-wider">Assignment</th>
            <th class="h-10 px-4 text-left font-medium text-muted text-xs uppercase tracking-wider">Created</th>
            <th class="h-10 px-4 text-left font-medium text-muted text-xs uppercase tracking-wider">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="safeImports.data.length === 0">
            <td colspan="7" class="px-4 py-12 text-center text-sm text-muted">
              No imports yet. Upload a CSV or XLSX file to get started.
            </td>
          </tr>
          <tr
            v-for="imp in safeImports.data"
            :key="imp.id"
            class="border-b border-border hover:bg-gray-50/50 transition-colors"
          >
            <td class="px-4 py-3">
              <div class="flex items-center gap-2">
                <FileUp class="h-4 w-4 text-muted-foreground" />
                <div>
                  <p class="text-sm font-medium text-foreground">{{ imp.original_filename }}</p>
                  <p class="text-xs text-muted uppercase">{{ imp.file_type }}</p>
                </div>
              </div>
            </td>
            <td class="px-4 py-3">
              <StatusBadge :status="imp.status" />
            </td>
            <td class="px-4 py-3">
              <div class="w-32">
                <Progress :value="imp.progress" :size="'sm'" />
                <p class="text-xs text-muted mt-1">{{ imp.progress }}%</p>
              </div>
            </td>
            <td class="px-4 py-3">
              <div class="text-xs space-y-0.5">
                <p>Total: {{ formatNumber(imp.total_rows) }}</p>
                <p class="text-success">Success: {{ formatNumber(imp.successful_rows) }}</p>
                <p v-if="imp.failed_rows" class="text-danger">Failed: {{ formatNumber(imp.failed_rows) }}</p>
                <p v-if="imp.duplicate_rows" class="text-warning">Duplicates: {{ formatNumber(imp.duplicate_rows) }}</p>
              </div>
            </td>
            <td class="px-4 py-3">
              <div class="max-w-64 space-y-1.5">
                <div class="flex flex-wrap items-center gap-1">
                  <span
                    v-for="list in firstItems(imp.lists)"
                    :key="`list-${list.name}`"
                    class="inline-flex max-w-32 items-center rounded-full border border-blue-200 bg-blue-50 px-2 py-0.5 text-xs font-medium text-blue-700"
                  >
                    <span class="truncate">{{ list.name }}</span>
                  </span>
                  <BadgeOverflowPopover
                    v-if="remainingItems(imp.lists).length"
                    :items="remainingItems(imp.lists)"
                    title="Lists"
                    tone="list"
                  />
                  <span v-if="!imp.lists?.length" class="text-xs text-muted">No lists</span>
                </div>
                <div class="flex flex-wrap items-center gap-1">
                  <span
                    v-for="tag in firstItems(imp.tags)"
                    :key="`tag-${tag.name}`"
                    class="inline-flex max-w-32 items-center rounded-full border border-primary/20 bg-primary/10 px-2 py-0.5 text-xs font-medium text-primary"
                  >
                    <span class="truncate">{{ tag.name }}</span>
                  </span>
                  <BadgeOverflowPopover
                    v-if="remainingItems(imp.tags).length"
                    :items="remainingItems(imp.tags)"
                    title="Tags"
                    tone="tag"
                  />
                  <span v-if="!imp.tags?.length" class="text-xs text-muted">No tags</span>
                </div>
              </div>
            </td>
            <td class="px-4 py-3">
              <p class="text-sm text-muted">{{ formatDate(imp.created_at) }}</p>
              <p class="text-xs text-muted-foreground">{{ imp.created_by_name }}</p>
            </td>
            <td class="px-4 py-3">
              <Link :href="route('imports.show', imp.id)">
                <Button variant="ghost" size="icon-sm">
                  <Eye class="h-4 w-4" />
                </Button>
              </Link>
            </td>
          </tr>
        </tbody>
      </table>
      </div>
    </div>
    <Pagination :meta="safeImports.meta" />
  </AppLayout>
</template>
