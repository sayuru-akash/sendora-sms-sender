<script setup lang="ts">
import { computed, ref } from 'vue';
import AppLayout from '@/Components/layout/AppLayout.vue';
import PageHeader from '@/Components/common/PageHeader.vue';
import StatusBadge from '@/Components/common/StatusBadge.vue';
import Pagination from '@/Components/common/Pagination.vue';
import EmptyState from '@/Components/common/EmptyState.vue';
import Card from '@/Components/ui/Card.vue';
import CardContent from '@/Components/ui/CardContent.vue';
import Button from '@/Components/ui/Button.vue';
import Select from '@/Components/ui/Select.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { Users, ArrowLeft } from 'lucide-vue-next';
import type { Campaign, CampaignRecipient } from '@/types/campaign';
import type { Pagination as PaginationType } from '@/types';
import { formatDateTime } from '@/lib/utils';

const props = defineProps<{
  campaign: Campaign;
  recipients?: { data: CampaignRecipient[]; meta: PaginationType };
  filters?: { status?: string; per_page?: string };
}>();

const emptyMeta: PaginationType = {
  current_page: 1,
  last_page: 1,
  per_page: 25,
  total: 0,
  from: null,
  to: null,
};

const safeRecipients = computed(() => props.recipients ?? { data: [], meta: emptyMeta });
const safeFilters = computed(() => props.filters ?? {});
const statusFilter = ref(safeFilters.value.status ?? '');

const statusOptions = [
  { label: 'All Statuses', value: '' },
  { label: 'Pending', value: 'pending' },
  { label: 'Queued', value: 'queued' },
  { label: 'Sent', value: 'sent' },
  { label: 'Failed', value: 'failed' },
  { label: 'Skipped', value: 'skipped' },
];

function applyFilter() {
  router.get(
    route('campaigns.recipients', props.campaign.id),
    { status: statusFilter.value || undefined },
    { preserveState: true },
  );
}
</script>

<template>
  <Head :title="`Recipients: ${campaign.name}`" />

  <AppLayout :breadcrumbs="[
    { label: 'Campaigns', href: route('campaigns.index') },
    { label: campaign.name, href: route('campaigns.show', campaign.id) },
    { label: 'Recipients' },
  ]">
    <PageHeader :title="`Recipients: ${campaign.name}`" :subtitle="`${safeRecipients.meta.total} recipients`">
      <template #actions>
        <Link :href="route('campaigns.show', campaign.id)">
          <Button variant="outline">
            <ArrowLeft class="h-4 w-4" />
            Back to Campaign
          </Button>
        </Link>
      </template>
    </PageHeader>

    <!-- Filter -->
    <div class="flex items-center gap-3 mb-4">
      <div class="w-48">
        <Select v-model="statusFilter" :options="statusOptions" @update:model-value="applyFilter" />
      </div>
    </div>

    <!-- Recipients Table -->
    <div v-if="safeRecipients.data.length === 0" class="py-8">
      <EmptyState
        :icon="Users"
        title="No recipients found"
        description="There are no recipients matching the current filter."
      />
    </div>

    <div v-else class="rounded-xl border border-border bg-white overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="border-b border-border">
              <th class="h-10 px-4 text-left font-medium text-muted text-xs uppercase tracking-wider">Contact Name</th>
              <th class="h-10 px-4 text-left font-medium text-muted text-xs uppercase tracking-wider">Phone</th>
              <th class="h-10 px-4 text-left font-medium text-muted text-xs uppercase tracking-wider">Status</th>
              <th class="h-10 px-4 text-left font-medium text-muted text-xs uppercase tracking-wider">Sent At</th>
              <th class="h-10 px-4 text-left font-medium text-muted text-xs uppercase tracking-wider">Error</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="recipient in safeRecipients.data"
              :key="recipient.id"
              class="border-b border-border hover:bg-gray-50/50 transition-colors"
            >
              <td class="px-4 py-3 text-sm text-foreground font-medium">
                {{ recipient.contact_name ?? recipient.contact?.full_name ?? 'N/A' }}
              </td>
              <td class="px-4 py-3 text-sm text-foreground">
                {{ recipient.contact_phone ?? recipient.contact?.phone_normalised ?? recipient.contact?.phone ?? '—' }}
              </td>
              <td class="px-4 py-3">
                <StatusBadge :status="recipient.status" />
              </td>
              <td class="px-4 py-3 text-sm text-muted">
                {{ recipient.sent_at ? formatDateTime(recipient.sent_at) : '—' }}
              </td>
              <td class="px-4 py-3 text-xs text-danger">
                {{ recipient.error_message ?? '—' }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <Pagination :meta="safeRecipients.meta" />
    </div>
  </AppLayout>
</template>
