<script setup lang="ts">
import AppLayout from '@/Components/layout/AppLayout.vue';
import PageHeader from '@/Components/common/PageHeader.vue';
import StatusBadge from '@/Components/common/StatusBadge.vue';
import Card from '@/Components/ui/Card.vue';
import Button from '@/Components/ui/Button.vue';
import Badge from '@/Components/ui/Badge.vue';
import Progress from '@/Components/ui/Progress.vue';
import { Head, Link } from '@inertiajs/vue3';
import { Upload, FileUp, Eye } from 'lucide-vue-next';
import type { Import } from '@/types/import';
import type { Pagination } from '@/types';
import { formatDate, formatNumber } from '@/lib/utils';

defineProps<{
  imports: { data: Import[]; meta: Pagination };
}>();
</script>

<template>
  <Head title="Imports" />

  <AppLayout :breadcrumbs="[{ label: 'Imports' }]">
    <PageHeader title="Imports" subtitle="Manage your contact imports">
      <template #actions>
        <Link :href="route('imports.upload')">
          <Button>
            <Upload class="h-4 w-4" />
            Upload File
          </Button>
        </Link>
      </template>
    </PageHeader>

    <div class="rounded-xl border border-border bg-white overflow-hidden">
      <table class="w-full text-sm">
        <thead>
          <tr class="border-b border-border">
            <th class="h-10 px-4 text-left font-medium text-muted text-xs uppercase tracking-wider">File</th>
            <th class="h-10 px-4 text-left font-medium text-muted text-xs uppercase tracking-wider">Status</th>
            <th class="h-10 px-4 text-left font-medium text-muted text-xs uppercase tracking-wider">Progress</th>
            <th class="h-10 px-4 text-left font-medium text-muted text-xs uppercase tracking-wider">Rows</th>
            <th class="h-10 px-4 text-left font-medium text-muted text-xs uppercase tracking-wider">Created</th>
            <th class="h-10 px-4 text-left font-medium text-muted text-xs uppercase tracking-wider">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="imports.data.length === 0">
            <td colspan="6" class="px-4 py-12 text-center text-sm text-muted">
              No imports yet. Upload a CSV or XLSX file to get started.
            </td>
          </tr>
          <tr
            v-for="imp in imports.data"
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
  </AppLayout>
</template>
