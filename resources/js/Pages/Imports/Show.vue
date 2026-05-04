<script setup lang="ts">
import AppLayout from '@/Components/layout/AppLayout.vue';
import PageHeader from '@/Components/common/PageHeader.vue';
import Card from '@/Components/ui/Card.vue';
import CardContent from '@/Components/ui/CardContent.vue';
import CardHeader from '@/Components/ui/CardHeader.vue';
import CardTitle from '@/Components/ui/CardTitle.vue';
import Button from '@/Components/ui/Button.vue';
import StatusBadge from '@/Components/common/StatusBadge.vue';
import Progress from '@/Components/ui/Progress.vue';
import StatCard from '@/Components/common/StatCard.vue';
import CampaignProgress from '@/Components/common/CampaignProgress.vue';
import { Head } from '@inertiajs/vue3';
import { Download, FileUp, CheckCircle, XCircle, Copy, AlertTriangle } from 'lucide-vue-next';
import type { Import } from '@/types/import';
import { formatNumber, formatDateTime } from '@/lib/utils';
import { computed } from 'vue';

const props = defineProps<{
  import: Import;
}>();

const importData = computed(() => props.import);
const imp = props.import;

function downloadFailedRows() {
  window.open(route('imports.download-failed', props.import.id));
}

const stats = [
  { label: 'Total Rows', value: imp.total_rows, icon: FileUp, color: 'text-primary', bg: 'bg-primary-light' },
  { label: 'Successful', value: imp.successful_rows, icon: CheckCircle, color: 'text-success', bg: 'bg-success-light' },
  { label: 'Failed', value: imp.failed_rows, icon: XCircle, color: 'text-danger', bg: 'bg-danger-light' },
  { label: 'Duplicates', value: imp.duplicate_rows, icon: Copy, color: 'text-warning', bg: 'bg-warning-light' },
];
</script>

<template>
  <Head :title="`Import: ${importData.original_filename}`" />

  <AppLayout :breadcrumbs="[
    { label: 'Imports', href: route('imports.index') },
    { label: importData.original_filename },
  ]">
    <PageHeader :title="importData.original_filename" :subtitle="`Imported by ${importData.created_by_name}`">
      <template #actions>
        <StatusBadge :status="importData.status" />
        <Button v-if="importData.failed_rows > 0" variant="outline" @click="downloadFailedRows()">
          <Download class="h-4 w-4" />
          Download Failed
        </Button>
      </template>
    </PageHeader>

    <!-- Stats -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
      <StatCard
        v-for="stat in stats"
        :key="stat.label"
        :label="stat.label"
        :value="stat.value"
        :icon="stat.icon"
        :icon-color="stat.color"
        :icon-bg="stat.bg"
      />
    </div>

    <!-- Progress -->
    <Card class="mb-6">
      <CardContent class="pt-6">
        <CampaignProgress
          :sent="imp.successful_rows"
          :failed="imp.failed_rows"
          :pending="imp.total_rows - imp.processed_rows"
          :total="imp.total_rows"
        />
      </CardContent>
    </Card>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- Import Details -->
      <Card>
        <CardHeader>
          <CardTitle>Import Details</CardTitle>
        </CardHeader>
        <CardContent>
          <dl class="space-y-3 text-sm">
            <div class="flex justify-between">
              <dt class="text-muted">File Type</dt>
              <dd class="font-medium text-foreground uppercase">{{ importData.file_type }}</dd>
            </div>
            <div class="flex justify-between">
              <dt class="text-muted">Duplicate Handling</dt>
              <dd class="font-medium text-foreground capitalize">{{ importData.duplicate_handling }}</dd>
            </div>
            <div class="flex justify-between">
              <dt class="text-muted">Phone Column</dt>
              <dd class="font-medium text-foreground">{{ importData.phone_column ?? '—' }}</dd>
            </div>
            <div class="flex justify-between">
              <dt class="text-muted">Created</dt>
              <dd class="font-medium text-foreground">{{ formatDateTime(importData.created_at) }}</dd>
            </div>
            <div v-if="importData.completed_at" class="flex justify-between">
              <dt class="text-muted">Completed</dt>
              <dd class="font-medium text-foreground">{{ formatDateTime(importData.completed_at) }}</dd>
            </div>
          </dl>
        </CardContent>
      </Card>

      <!-- Failed Rows -->
      <Card v-if="importData.failed_rows_data?.length">
        <CardHeader>
          <CardTitle>Failed Rows</CardTitle>
        </CardHeader>
        <CardContent>
          <div class="space-y-2 max-h-[400px] overflow-y-auto">
            <div
              v-for="row in importData.failed_rows_data.slice(0, 20)"
              :key="row.row_number"
              class="rounded-lg border border-danger/20 bg-danger-light/50 p-3"
            >
              <div class="flex items-center justify-between mb-1">
                <span class="text-xs font-medium text-danger">Row {{ row.row_number }}</span>
                <AlertTriangle class="h-3.5 w-3.5 text-danger" />
              </div>
              <p class="text-xs text-danger/80">{{ row.error }}</p>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
